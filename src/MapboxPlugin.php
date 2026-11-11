<?php
/**
 * Mapbox plugin for Craft CMS
 *
 * Maps in minutes. Powered by the Mapbox API.
 *
 * @author    Double Secret Agency
 * @link      https://plugins.doublesecretagency.com/
 * @copyright Copyright (c) 2023 Double Secret Agency
 */

namespace doublesecretagency\mapbox;

use Craft;
use craft\base\Field;
use craft\base\Model;
use craft\base\Plugin;
use craft\db\Query;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\DefineCompatibleFieldTypesEvent;
use craft\events\ModelEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use doublesecretagency\mapbox\fields\AddressField;
use doublesecretagency\mapbox\migrations\Install;
use doublesecretagency\mapbox\models\Settings;
use doublesecretagency\mapbox\web\twig\Extension;
use Throwable;
use yii\base\Event;

/**
 * Class MapboxPlugin
 * @since 1.0.0
 */
class MapboxPlugin extends Plugin
{

    /**
     * @var bool The plugin has a settings page.
     */
    public bool $hasCpSettings = true;

    /**
     * @var string Current schema version of the plugin.
     */
    public string $schemaVersion = '1.2.0';

    /**
     * @var MapboxPlugin Self-referential plugin property.
     */
    public static MapboxPlugin $plugin;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Load Twig extension
        Craft::$app->getView()->registerTwigExtension(new Extension());

        // Register all events
        $this->_registerFieldType();
        $this->_registerCompatibleFieldTypes();

        // Manage conversions of the Address field
        $this->_manageFieldTypeConversions();

        // Redirect after plugin install
        $this->_postInstallRedirect();

        // Normalize the subfield configuration
        $this->_normalizeSubfieldConfig();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        // Reference assets
        $view = Craft::$app->getView();

        // Get data from config file
        $configFile = Craft::$app->getConfig()->getConfigFromFile('mapbox');

        // Load plugin settings template
        return $view->renderTemplate('mapbox/settings', [
            'configFile' => $configFile,
            'settings' => $this->getSettings(),
        ]);
    }

    // ========================================================================= //

    /**
     * Register the field type.
     *
     * @return void
     */
    private function _registerFieldType(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function (RegisterComponentTypesEvent $event) {
                $event->types[] = AddressField::class;
            }
        );
    }

    /**
     * Register all field type compatibility.
     * Declare THIS field safe for OTHER fields.
     *
     * @return void
     */
    private function _registerCompatibleFieldTypes(): void
    {
        // If unable to mark fields as compatible, bail (requires Craft 4.5.7+)
        if (!class_exists(DefineCompatibleFieldTypesEvent::class)) {
            return;
        }
        // Mark fields as compatible
        Event::on(
            Fields::class,
            Fields::EVENT_DEFINE_COMPATIBLE_FIELD_TYPES,
            static function (DefineCompatibleFieldTypesEvent $event) {
                // If it's a Google Maps field
                if (is_a($event->field, 'doublesecretagency\googlemaps\fields\AddressField')) {
                    // Tell it that the Mapbox field is compatible
                    $event->compatibleTypes[] = AddressField::class;
                }
            }
        );
    }

    /**
     * Manage conversions of the Address field from the Google Maps plugin.
     *
     * @return void
     */
    private function _manageFieldTypeConversions(): void
    {
        // If Google Maps plugin is not installed and enabled, bail
        if (!Craft::$app->getPlugins()->isPluginEnabled('google-maps')) {
            return;
        }

        // When a single project config line gets updated
        Event::on(
            ProjectConfig::class,
            ProjectConfig::EVENT_UPDATE_ITEM,
            static function (ConfigEvent $event) {

                // Get old and new types
                $oldType = $event->oldValue['type'] ?? null;
                $newType = $event->newValue['type'] ?? null;

                // If old type wasn't a Google Maps Address field, bail
                if (!($oldType === 'doublesecretagency\googlemaps\fields\AddressField')) {
                    return;
                }

                // If new type is not a Mapbox Address field, bail
                if (!($newType === 'doublesecretagency\mapbox\fields\AddressField')) {
                    return;
                }

                // Get the field's UID
                $uid = str_replace('fields.', '', $event->path);

                // Get the actual field
                $field = Craft::$app->getFields()->getFieldByUid($uid);

                // If unable to get the field, bail
                if (!$field) {
                    return;
                }

                // Copy the field's Addresses from Google Maps
                self::_copyGoogleMapsAddresses((int) $field->id);

            }
        );
    }

    /**
     * Copy a converted field's Addresses from Google Maps into Mapbox.
     *
     * Runs once, when the field type changes, so it copies all or nothing.
     *
     * @param int $fieldId
     * @throws Throwable
     */
    private static function _copyGoogleMapsAddresses(int $fieldId): void
    {
        // Get the database connection
        $db = Craft::$app->getDb();

        // List of columns to copy between tables
        $columns = [
            'elementId', 'fieldId',
            'formatted', 'raw',
            'name', 'street1', 'street2',
            'city', 'state', 'zip',
            'county', 'country',
            'lng', 'lat', 'zoom',
            'dateCreated', 'dateUpdated',
        ];

        // Newer versions of Google Maps also store a neighborhood
        if ($db->columnExists('{{%googlemaps_addresses}}', 'neighborhood')) {
            $columns[] = 'neighborhood';
        }

        // Google Maps 4.6+ and 5.1+ store a site for each Address
        $hasSites = $db->columnExists('{{%googlemaps_addresses}}', 'siteId');

        // Get the primary site ID
        $primarySiteId = (int) Craft::$app->getSites()->getPrimarySite()->id;

        // Get this field's Google Maps Addresses
        $addresses = (new Query())
            ->select($hasSites ? [...$columns, 'siteId'] : $columns)
            ->from('{{%googlemaps_addresses}}')
            ->where(['fieldId' => $fieldId])
            ->orderBy(['id' => SORT_ASC]);

        // Copy everything, or nothing
        $db->transaction(static function () use ($db, $addresses, $columns, $fieldId, $primarySiteId) {

            // Get each element and site which already has a Mapbox Address
            $taken = [];
            foreach ((new Query())->select(['elementId', 'siteId'])->from(Install::MAPBOX_ADDRESSES)->where(['fieldId' => $fieldId])->all($db) as $row) {
                $taken["{$row['elementId']}:{$row['siteId']}"] = true;
            }

            // Loop over each batch of Addresses
            foreach (Db::batch($addresses) as $batch) {

                // Get the sites of each element in this batch
                $elementSites = [];
                $siteRows = (new Query())
                    ->select(['elementId', 'siteId'])
                    ->from(Table::ELEMENTS_SITES)
                    ->where(['elementId' => array_column($batch, 'elementId')])
                    ->all($db);
                foreach ($siteRows as $row) {
                    $elementSites[(int) $row['elementId']][] = (int) $row['siteId'];
                }

                // Compile the new rows
                $rows = [];
                foreach ($batch as $address) {

                    // Get the sites of its element, or else the primary site
                    $elementId = (int) $address['elementId'];
                    $siteIds = ($elementSites[$elementId] ?? [$primarySiteId]);

                    // If the Address has its own site, copy to that site only (if the element exists there)
                    if ($address['siteId'] ?? null) {
                        $siteIds = array_intersect($siteIds, [(int) $address['siteId']]);
                    }

                    // Loop over each site
                    foreach ($siteIds as $siteId) {

                        // If this site already has an Address, skip
                        if (isset($taken["{$elementId}:{$siteId}"])) {
                            continue;
                        }

                        // Mark the site as taken
                        $taken["{$elementId}:{$siteId}"] = true;

                        // Add the row, with its own site (Craft adds the UID)
                        $values = array_map(static fn(string $column) => $address[$column], $columns);
                        $rows[] = [...$values, $siteId];

                    }
                }

                // Insert the batch
                if ($rows) {
                    Db::batchInsert(Install::MAPBOX_ADDRESSES, [...$columns, 'siteId'], $rows, $db);
                }

            }

        });
    }

    // ========================================================================= //

    /**
     * After the plugin has been installed,
     * redirect to the "Welcome" settings page.
     *
     * @return void
     */
    private function _postInstallRedirect(): void
    {
        // After the plugin has been installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            static function (PluginEvent $event) {

                // If installed plugin isn't Mapbox, bail
                if ('mapbox' !== $event->plugin->handle) {
                    return;
                }

                // If installed via console, no need for a redirect
                if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                    return;
                }

                // Redirect to the plugin's settings page (with a welcome message)
                $url = UrlHelper::cpUrl('settings/plugins/mapbox', ['welcome' => 1]);
                Craft::$app->getResponse()->redirect($url)->send();
            }
        );
    }

    /**
     * When the field settings are saved,
     * normalize the subfield configuration.
     *
     * @return void
     */
    private function _normalizeSubfieldConfig(): void
    {
        // Adjust field settings when they are saved
        Event::on(
            Field::class,
            Field::EVENT_BEFORE_SAVE,
            function (ModelEvent $event) {

                // Get field settings
                $fieldSettings = $event->sender;

                // If no subfield config, bail
                if (!($fieldSettings->subfieldConfig ?? false)) {
                    return;
                }

                // Strictly typecast all subfield settings
                AddressField::typecastSubfieldConfig($fieldSettings->subfieldConfig);
            }
        );
    }

}
