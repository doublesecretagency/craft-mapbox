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
use craft\events\ModelEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\services\Plugins;
use doublesecretagency\mapbox\fields\AddressField;
use doublesecretagency\mapbox\models\Settings;
use doublesecretagency\mapbox\web\assets\SettingsAsset;
use doublesecretagency\mapbox\web\twig\Extension;
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
    public string $schemaVersion = '1.0.0';

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
        $view->registerAssetBundle(SettingsAsset::class);

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
