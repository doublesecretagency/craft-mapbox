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

namespace doublesecretagency\mapbox\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\web\View;
use doublesecretagency\mapbox\enums\Defaults;
use doublesecretagency\mapbox\helpers\Mapbox;
use doublesecretagency\mapbox\MapboxPlugin;
use doublesecretagency\mapbox\models\Address as AddressModel;
use doublesecretagency\mapbox\records\Address as AddressRecord;
use doublesecretagency\mapbox\validators\AddressValidator;
use doublesecretagency\mapbox\web\assets\AddressFieldAsset;
use doublesecretagency\mapbox\web\assets\AddressFieldSettingsAsset;

/**
 * Class AddressField
 * @since 1.0.0
 */
class AddressField extends Field implements PreviewableFieldInterface
{

    /**
     * Whether to show the map.
     *
     * @var bool
     */
    public bool $showMap = false;

    /**
     * What should the map be
     * when the field is initially loaded?
     *
     * @var string "default", "open" or "close"
     */
    public string $mapOnStart = 'default';

    /**
     * What should the map be
     * when a geocode lookup is performed?
     *
     * @var string "open", "close" or "noChange"
     */
    public string $mapOnSearch = 'open';

    /**
     * How should we display
     * the map visibility toggle?
     *
     * @var string "both", "text", "icon" or "hidden"
     */
    public string $visibilityToggle = 'both';

    /**
     * How should we display
     * the coordinates fields?
     *
     * @var string "editable", "readOnly" or "hidden"
     */
    public string $coordinatesMode = 'readOnly';

    /**
     * Whether the coordinates subfields are required.
     *
     * @var bool
     */
    public bool $requireCoordinates = true;

    /**
     * Default coordinates of a new Address field.
     *
     * @var array|null
     */
    public ?array $coordinatesDefault = Defaults::COORDINATES;

    /**
     * Full configuration of subfields.
     *
     * @var array|null
     */
    public ?array $subfieldConfig = Defaults::SUBFIELDCONFIG;

    /**
     * Icons to be loaded.
     *
     * @var array
     */
    private array $_icons = [
        'iconOn' => 'marker.svg',
        'iconOff' => 'marker-hollow.svg',
        // https://github.com/mapbox/maki/tree/main/icons
        'maki-lodging' => 'maki/lodging.svg',
        'maki-restaurant' => 'maki/restaurant.svg',
        'maki-bar' => 'maki/bar.svg',
        'maki-fuel' => 'maki/fuel.svg',
        'maki-cinema' => 'maki/cinema.svg',
        'maki-library' => 'maki/library.svg',
        'maki-marker' => 'maki/marker.svg',
        'maki-default' => 'maki/marker-stroked.svg',
    ];

    // ========================================================================= //

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('mapbox', 'Address');
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    // ========================================================================= //

    /**
     * After saving element, save field to plugin table.
     *
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        /** @var Entry $element */

        // Get field data
        $data = $element->getFieldValue($this->handle);

        // If data doesn't exist, bail
        if (!$data) {
            return;
        }

        // Attempt to load an existing record
        $record = AddressRecord::findOne([
            'elementId' => $element->id,
            'fieldId'   => $this->id,
        ]);

        // If no record exists, create a new record
        if (!$record) {
            $record = new AddressRecord([
                'elementId' => $element->id,
                'fieldId'   => $this->id,
            ]);
        }

        // Get coordinates
        $lng  = ($data['lng']  ?: null);
        $lat  = ($data['lat']  ?: null);
        $zoom = ($data['zoom'] ?: null);

        // If coords are valid but zoom is missing
        if ($lng && $lat && !$zoom) {
            // Fallback to zero by default
            $zoom = 0;
        }

        // Set record attributes
        $record->setAttributes([
            'formatted'    => ($data['formatted'] ?: null),
            'raw'          => ($data['raw'] ?: null),
            'name'         => ($data['name'] ?: null),
            'street1'      => ($data['street1'] ?: null),
            'street2'      => ($data['street2'] ?: null),
            'city'         => ($data['city'] ?: null),
            'state'        => ($data['state'] ?: null),
            'zip'          => ($data['zip'] ?: null),
            'neighborhood' => ($data['neighborhood'] ?: null),
            'county'       => ($data['county'] ?: null),
            'country'      => ($data['country'] ?: null),
            'mapboxId'     => ($data['mapboxId'] ?: null),
            'lng'          => $lng,
            'lat'          => $lat,
            'zoom'         => $zoom,
        ], false);

        // Save record
        $record->save();
    }

    /**
     * As the data leaves the database, prepare the Address value for use.
     *
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): ?AddressModel
    {
        /** @var Entry $element */

        // If the value is already an Address model, return it immediately
        if ($value instanceof AddressModel) {
            return $value;
        }

        // If value is an array, load it directly into an Address model
        if (is_array($value)) {
            // Get coordinates
            $lng  = ($value['lng']  ?? null);
            $lat  = ($value['lat']  ?? null);
            $zoom = ($value['zoom'] ?? null);
            // If coords are valid but zoom is missing
            if ($lng && $lat && !$zoom) {
                // Fallback to zero by default
                $zoom = 0;
            }
            // Return Address model
            return new AddressModel([
                'elementId'    => (int) ($element->id ?? null),
                'fieldId'      => (int) ($this->id ?? null),
                'formatted'    => (($value['formatted'] ?? null) ?: null),
                'raw'          => (($value['raw'] ?? null) ?: null),
                'name'         => ($value['name'] ?? null),
                'street1'      => ($value['street1'] ?? null),
                'street2'      => ($value['street2'] ?? null),
                'city'         => ($value['city'] ?? null),
                'state'        => ($value['state'] ?? null),
                'zip'          => ($value['zip'] ?? null),
                'neighborhood' => ($value['neighborhood'] ?? null),
                'county'       => ($value['county'] ?? null),
                'country'      => ($value['country'] ?? null),
                'mapboxId'     => ($value['mapboxId'] ?? null),
                'lng'          => (is_numeric($lng) ? (float) $lng : null),
                'lat'          => (is_numeric($lat) ? (float) $lat : null),
                'zoom'         => (is_numeric($zoom) ? (float) $zoom : null),
            ]);
        }

        // If no element or no field ID, bail
        if (!$element || !$this->id) {
            return null;
        }

        // Attempt to load existing record
        $record = AddressRecord::findOne([
            'elementId' => $element->id,
            'fieldId' => $this->id,
        ]);

        // If no matching record exists, bail
        if (!$record) {
            return null;
        }

        // Get the record attributes
        $omitColumns = ['dateCreated','dateUpdated','uid'];
        $attr = $record->getAttributes(null, $omitColumns);

        // Convert coordinates to floats
        $attr['lng']  = ($attr['lng']  ? (float) $attr['lng']  : null);
        $attr['lat']  = ($attr['lat']  ? (float) $attr['lat']  : null);
        $attr['zoom'] = ($attr['zoom'] ? (float) $attr['zoom'] : null);

        // If coords are valid but zoom is missing
        if ($attr['lng'] && $attr['lat'] && !$attr['zoom']) {
            // Fallback to zero by default
            $attr['zoom'] = 0;
        }

        // Check if JSON is valid
        // Must use this function to validate (I know it's redundant)
        $valid = json_decode($attr['raw']);

        // Convert raw data to an array
        $attr['raw'] = ($valid ? Json::decode($attr['raw']) : null);

        // If part of a proximity search, get the distance
        if ($value && is_numeric($value)) {
            $attr['distance'] = (float) $value;
        }

        // Return an Address model
        return new AddressModel($attr);
    }

    // ========================================================================= //

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        // If not required, skip validation
        if (!$this->required) {
            return [];
        }

        // Apply validation rule
        return [
            [AddressValidator::class]
        ];
    }

    // ========================================================================= //

    /**
     * @inheritdoc
     */
    public function getInputHtml(mixed $address, ?ElementInterface $element = null): string
    {
        // Whether the field has existing coordinates
        $coordsExist = ($address instanceof AddressModel && $address->hasCoords());

        // Get extended settings
        $settings = $this->_getExtraSettings();

        // By default, show map if coordinates exist
        if ('default' === $settings['mapOnStart']) {
            $settings['showMap'] = $coordsExist;
            $settings['mapOnStart'] = ($coordsExist ? 'open' : 'close');
        }

        // Load view service
        $view = Craft::$app->getView();

        // Pass access token through to JavaScript
        $accessToken = Mapbox::getAccessToken();
        $view->registerJs("window.mapboxAccessToken='{$accessToken}';", View::POS_HEAD);

        // Register assets
        $view->registerAssetBundle(AddressFieldAsset::class);

        // Load fieldtype input template
        return $view->renderTemplate('mapbox/address', [
            'config' => [
                'namespace' => [
                    'id' => $view->namespaceInputId($this->handle),
                    'name' => $view->namespaceInputName($this->handle),
                    'handle' => $this->handle,
                ],
                'settings' => $settings,
                'data' => $this->_getAddressData($address),
                'images' => $this->_publishImages($this->_icons),
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Load view service
        $view = Craft::$app->getView();

        // Pass access token through to JavaScript
        $accessToken = Mapbox::getAccessToken();
        $view->registerJs("window.mapboxAccessToken='{$accessToken}';", View::POS_HEAD);

        // Register assets
        $view->registerAssetBundle(AddressFieldSettingsAsset::class);

        // Load fieldtype settings template
        return $view->renderTemplate('mapbox/address-settings', [
            'config' => [
                'namespace' => [
                    'id' => $view->namespaceInputId('PLACEHOLDER'),
                    'name' => $view->namespaceInputName('PLACEHOLDER'),
                    'handle' => 'PLACEHOLDER',
                ],
                'settings' => $this->_getExtraSettings(),
                'data' => $this->_getAddressData(),
                'images' => $this->_publishImages(
                    array_merge(
                        $this->_icons,
                        ['required' => 'required.png']
                    )
                ),
            ]
        ]);
    }

    // ========================================================================= //

    /**
     * Typecast the subfield configuration.
     *
     * @param $subfieldConfig
     */
    public static function typecastSubfieldConfig(&$subfieldConfig): void
    {
        // Strictly typecast each subfield setting
        array_walk($subfieldConfig, static function (&$value) {
            $value = [
                'handle'       => (string) ($value['handle'] ?? ''),
                'label'        => (string) ($value['label'] ?? ''),
                'width'        => (int) ($value['width'] ?? 100),
                'enabled'      => (bool) ($value['enabled'] ?? false),
                'autocomplete' => (bool) ($value['autocomplete'] ?? false),
                'required'     => (bool) ($value['required'] ?? false),
            ];
        });
    }

    // ========================================================================= //

    /**
     * Normalize the subfield configuration.
     *
     * @param $subfieldConfig
     * @return array
     */
    private function _normalizeSubfieldConfig($subfieldConfig): array
    {
        // What kind of array is the subfield configuration?
        $isSequential  = (array_key_exists(0, $subfieldConfig));         // (NEW STYLE)
        $isAssociative = (array_key_exists('street1', $subfieldConfig)); // (OLD STYLE)

        // If it's a sequential array
        if ($isSequential) {
            // Strictly typecast all subfield settings
            static::typecastSubfieldConfig($subfieldConfig);

            // Get a list of all subfield handles which already exist
            $existingHandles = array_map(
                static function ($item) {
                    return $item['handle'];
                },
                $subfieldConfig
            );

            // If handle doesn't exist, add it to list of missing subfields
            $missing = array_filter(
                Defaults::SUBFIELDCONFIG,
                static function ($item) use ($existingHandles) {
                    // Return whether handle does not exist
                    return !in_array($item['handle'], $existingHandles, true);
                }
            );

            // Append any missing subfields
            $subfieldConfig = array_merge($subfieldConfig, $missing);

            // Return the subfield config
            return $subfieldConfig;
        }

        // If it's NOT an associative array
        if (!$isAssociative) {
            // It's misconfigured, return the default configuration
            return Defaults::SUBFIELDCONFIG;
        }

        // Initialize new config
        $newConfig = [];

        // Loop through default subfield configuration
        foreach (Defaults::SUBFIELDCONFIG as $defaultConfig) {

            // Get the existing config
            $oldConfig = ($subfieldConfig[$defaultConfig['handle']] ?? []);

            // Append new config for each subfield
            $newConfig[] = [
                'handle'       => $defaultConfig['handle'],
                'label'        => (string) ($oldConfig['label'] ?? $defaultConfig['label']),
                'width'        => (int) ($oldConfig['width'] ?? $defaultConfig['width']),
                'enabled'      => (bool) ($oldConfig['enabled'] ?? false),
                'autocomplete' => (bool) ($oldConfig['autocomplete'] ?? false),
                'required'     => (bool) ($oldConfig['required'] ?? false),
            ];
        }

        // Reorder the new config based on the old config's `position` value
        usort($newConfig, static function ($a, $b) use ($subfieldConfig) {

            // Get original subfield configs
            $subfieldA = ($subfieldConfig[$a['handle']] ?? []);
            $subfieldB = ($subfieldConfig[$b['handle']] ?? []);

            // Get original positions
            $positionA = (int) ($subfieldA['position'] ?? 100);
            $positionB = (int) ($subfieldB['position'] ?? 101);

            // Return sorting results
            return ($positionA < $positionB) ? -1 : 1;
        });

        // Return new subfield config
        return $newConfig;
    }

    /**
     * Get the field settings with some extra information.
     *
     * @return array
     */
    private function _getExtraSettings(): array
    {
        // Get basic settings
        $settings = $this->getSettings();

        // Set whether to show the map on initial load
        $settings['showMap'] = ('open' === $settings['mapOnStart']);

        // Normalize the subfield config
        $settings['subfieldConfig'] = $this->_normalizeSubfieldConfig($settings['subfieldConfig'] ?? []);

        // Return settings
        return $settings;
    }

    /**
     * Extract data from an Address model,
     * or set everything to null if no model.
     *
     * @param AddressModel|null $address
     * @return array[]
     */
    private function _getAddressData(?AddressModel $address = null): array
    {
        // Get and JSON encode raw value
        $raw = ($address->raw ?? null);
        $raw = ($raw ? Json::encode($raw) : null);

        return [
            'address'=> [
                'formatted'    => ($address->formatted ?? null),
                'raw'          => $raw,
                'name'         => ($address->name ?? null),
                'street1'      => ($address->street1 ?? null),
                'street2'      => ($address->street2 ?? null),
                'city'         => ($address->city ?? null),
                'state'        => ($address->state ?? null),
                'zip'          => ($address->zip ?? null),
                'neighborhood' => ($address->neighborhood ?? null),
                'county'       => ($address->county ?? null),
                'country'      => ($address->country ?? null),
                'mapboxId'     => ($address->mapboxId ?? null),
            ],
            'coords'=> [
                'lng'  => ($address->lng ?? null),
                'lat'  => ($address->lat ?? null),
                'zoom' => ($address->zoom ?? null),
            ]
        ];
    }

    /**
     * Publish a set of images, returning their published URLs.
     *
     * @param array $images
     * @return array
     */
    private function _publishImages(array $images): array
    {
        // Load asset manager
        $assetManager = Craft::$app->getAssetManager();

        // Directory of images
        $directory = '@doublesecretagency/mapbox/web/assets/dist';

        // Publish each image, and change each value to the published URL
        array_walk($images, function (&$value) use ($assetManager, $directory) {
            $value = $assetManager->getPublishedUrl($directory, true, "images/{$value}");
        });

        // Return published images
        return $images;
    }

    // ========================================================================= //

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        // If options are not properly specified, bail
        if (!is_array($value)) {
            return;
        }

        // Modify the element query to perform a proximity search
//        ProximitySearchHelper::modifyElementsQuery($query, $value, $this);
    }

}
