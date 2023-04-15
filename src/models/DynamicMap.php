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

namespace doublesecretagency\mapbox\models;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\models\FieldLayout;
use craft\web\View;
use doublesecretagency\mapbox\fields\AddressField;
use doublesecretagency\mapbox\MapboxPlugin;
use doublesecretagency\mapbox\helpers\Mapbox;
use doublesecretagency\mapbox\helpers\MapHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;
use yii\base\Exception;

/**
 * Class DynamicMap
 * @since 1.0.0
 */
class DynamicMap extends Model
{

    /**
     * @var string The ID of this map model.
     */
    public string $id;

    /**
     * @var array Collection of internal data representing a map to be rendered.
     */
    private array $_dna = [];

    /**
     * @var array Collection of popups tied to markers.
     */
    private array $_popups = [];

    // ========================================================================= //

    /**
     * Can't output directly as a string (unfortunately)
     * because `__toString` isn't compatible with
     * the `raw` filter (necessary to show an HTML tag).
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'To display a map, append `.tag()` to the map object.';
    }

    /**
     * Initialize a Dynamic Map object.
     *
     * @param array|Element|Location|null $locations
     * @param array $options
     * @param array $config
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __construct(array|Element|Location|null $locations = [], array $options = [], array $config = [])
    {
        // Call parent constructor
        parent::__construct($config);

        // Ensure options are a valid array
        if (!$options || !is_array($options)) {
            $options = [];
        }

        // If no ID, automatically generate a random one
        if (!isset($options['id'])) {
            $hash = StringHelper::randomString(6);
            $options['id'] = "map-{$hash}";
        }

        // Set internal map ID
        $this->id = $options['id'];

        // Initialize map DNA without markers
        $this->_dna[] = [
            'type' => 'map',
            'locations' => [],
            'options' => $options,
        ];

        // Prevent conflict between map ID and marker IDs
        unset($options['id']);

        // Load all map markers
        $this->markers($locations, $options);
    }

    // ========================================================================= //

    /**
     * Add one or more markers to the map.
     *
     * @param array|Element|Location|null $locations
     * @param array $options
     * @return $this
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function markers(array|Element|Location|null $locations, array $options = []): DynamicMap
    {
        // If no locations were specified, bail
        if (!$locations) {
            return $this;
        }

        // Whether markers should be placed individually
        $uniqueMarkers = ($options['popupTemplate'] ?? false);

        // If adding markers individually
        if ($uniqueMarkers) {
            // Create individual markers one at a time
            $this->_individualMarkers($locations, $options);
        } else {
            // Add markers to DNA as a group
            $this->_dna[] = [
                'type' => 'markers',
                'locations' => MapHelper::extractCoords($locations, $options),
                'options' => $options,
            ];
        }

        // Keep the party going
        return $this;
    }

    // ========================================================================= //

    /**
     * Style the map.
     *
     * @param string|array $mapStyle
     * @return $this
     */
    public function style(string|array $mapStyle): DynamicMap
    {
        // Add map style to DNA
        $this->_dna[] = [
            'type' => 'style',
            'mapStyle' => $mapStyle,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Change zoom level of the map.
     *
     * @param int $level
     * @return $this
     */
    public function zoom(int $level): DynamicMap
    {
        // Add zoom level to DNA
        $this->_dna[] = [
            'type' => 'zoom',
            'level' => $level,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Re-center the map.
     *
     * @param array $coords
     * @return $this
     */
    public function center(array $coords): DynamicMap
    {
        // If not a valid style set, bail
        if (!$coords) {
            return $this;
        }

        // Add map center to DNA
        $this->_dna[] = [
            'type' => 'center',
            'coords' => $coords,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Fit map to existing marker bounds.
     *
     * @param array|null $options
     * @return $this
     */
    public function fit(?array $options): DynamicMap
    {
        // Add fitBounds to DNA
        $this->_dna[] = [
            'type' => 'fit',
            'options' => $options,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Pan map to center on a specific marker.
     *
     * @param string $markerId
     * @return $this
     */
    public function panToMarker(string $markerId): DynamicMap
    {
        // Add pan to marker to DNA
        $this->_dna[] = [
            'type' => 'panToMarker',
            'markerId' => $markerId,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Set new options for an existing marker.
     *
     * @param string|array $markerId
     * @param array $options
     * @return $this
     */
    public function changeMarker(string|array $markerId, array $options): DynamicMap
    {
        // Add marker options to DNA
        $this->_dna[] = [
            'type' => 'changeMarker',
            'markerId' => $markerId,
            'options' => $options,
        ];

        // Keep the party going
        return $this;
    }

    // ========================================================================= //

    /**
     * Hide a marker.
     *
     * @param string|array $markerId
     * @return $this
     */
    public function hideMarker(string|array $markerId): DynamicMap
    {
        // Add call to hide marker
        $this->_dna[] = [
            'type' => 'hideMarker',
            'markerId' => $markerId,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Show a marker.
     *
     * @param string|array $markerId
     * @return $this
     */
    public function showMarker(string|array $markerId): DynamicMap
    {
        // Add call to show marker
        $this->_dna[] = [
            'type' => 'showMarker',
            'markerId' => $markerId,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Open the popup of a specific marker.
     *
     * @param string|array $markerId
     * @return $this
     */
    public function openPopup(string|array $markerId): DynamicMap
    {
        // Add open popup to DNA
        $this->_dna[] = [
            'type' => 'openPopup',
            'markerId' => $markerId,
        ];

        // Keep the party going
        return $this;
    }

    /**
     * Close the popup of a specific marker.
     *
     * @param string|array $markerId
     * @return $this
     */
    public function closePopup(string|array $markerId): DynamicMap
    {
        // Add close popup to DNA
        $this->_dna[] = [
            'type' => 'closePopup',
            'markerId' => $markerId,
        ];

        // Keep the party going
        return $this;
    }

    // ========================================================================= //

    /**
     * Outputs a dynamic map in a `<div>` tag for use in a Twig template.
     *
     * @param array $options Set of options to configure the rendered tag.
     * @return Markup
     * @throws Exception
     */
    public function tag(array $options = []): Markup
    {
        // If no DNA, throw an error
        if (!$this->_dna) {
            throw new Exception('Model misconfigured. The map DNA is empty.');
        }

        // If the first DNA item is not a map, throw an error
        if ('map' !== ($this->_dna[0]['type'] ?? false)) {
            throw new Exception('Map model misconfigured. The chain must begin with a `map()` segment.');
        }

        // Unless otherwise specified, preload the necessary JavaScript assets
        if (!isset($options['assets']) || !is_bool($options['assets'])) {
            $options['assets'] = true;
        }

        // If no additional API parameters were specified, default to empty array
        if (!isset($options['params']) || !is_array($options['params'])) {
            $options['params'] = [];
        }

        // If we're permitted to load JS assets
        if ($options['assets']) {
            // Load assets with optional API parameters
            Mapbox::loadAssets('maps', $options['params']);
        }

        // Compile map container
        $html = Html::modifyTagAttributes('<div>Loading map...</div>', [
            'id' => $this->id,
            'class' => 'mb-map',
            'data-dna' => Json::encode($this->_dna),
        ]);

        // Get view service
        $view = Craft::$app->getView();

        // Render all additional JavaScript
        $javascript = $this->_additionalJs();

        // If using inline JavaScript
        if ($options['inline'] ?? false) {
            // Register JS inline, immediately after the map
            $html .= "\n<script>{$javascript}\n</script>";
        } else {
            // Register JS at the end of the page
            $view->registerJs($javascript, $view::POS_END);
        }

        // Initialize the map (unless intentionally suppressed)
        if ($options['init'] ?? true) {
            // Get optional callback
            $callback = ($options['callback'] ?? 'null');
            // Initialize Mapbox after page has loaded
            $mapboxInit = "mapbox.init('{$this->id}', {$callback})";
            $js = "addEventListener('load', function () {{$mapboxInit}});";
            // Register JS at the end of the page
            $view->registerJs($js, $view::POS_END);
        }

        // Return Markup
        return Template::raw($html);
    }

    // ========================================================================= //

    /**
     * Return the immutable DNA array.
     *
     * @return array
     */
    public function getDna(): array
    {
        return $this->_dna;
    }

    // ========================================================================= //

    /**
     * Create individual markers one at a time.
     *
     * @param array|Element|Location $locations
     * @param array $options
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function _individualMarkers(array|Element|Location $locations, array $options): void
    {
        // Initialize popupOptions
        $options = $options ?? [];
        $options['popupOptions'] = $options['popupOptions'] ?? [];

        // Whether value is a simple set of coordinates
        $isCoords = (is_array($locations) && isset($locations['lng'], $locations['lat']));

        // If it's an array, but not a coordinates set
        if (is_array($locations) && !$isCoords) {
            // Loop through each location
            foreach ($locations as $l) {
                // If the location is valid
                if ($l) {
                    // Call method recursively
                    $this->_individualMarkers($l, $options);
                }
            }
            // Our work here is done
            return;
        }

        /**
         * Due to recursion, locations are now
         * guaranteed to be a singular item
         */
        $location =& $locations;

        // Extract location coordinates
        $coords = MapHelper::extractCoords($location, $options);

        // If no coordinates, bail
        if (!$coords) {
            return;
        }

        // If popup template specified
        if ($options['popupTemplate'] ?? false) {
            // Get the marker ID
            $options['id'] = ($options['id'] ?? $coords[0]['id'] ?? null);
            // Add a popup for the marker
            $this->_markerPopup($location, $options, $isCoords);
        }

        // Remove popup and field info from DNA
        unset(
            $options['popupOptions'],
            $options['popupTemplate'],
            $options['field'],
        );

        // Add individual marker to DNA
        $this->_dna[] = [
            'type' => 'markers',
            'locations' => $coords,
            'options' => $options,
        ];
    }

    // ========================================================================= //

    /**
     * Creates a single marker with a corresponding popup.
     *
     * @param array|Element|Location $location
     * @param array $options
     * @param bool $isCoords
     */
    private function _markerPopup(array|Element|Location $location, array &$options, bool $isCoords): void
    {
        // Initialize marker data
        $popup = [
            'mapId' => $this->id,
            'markerId' => ($options['id'] ?? null)
        ];

        // If location is a set of coordinates
        if ($isCoords) {

            // Set only the coordinates
            $popup['coords'] = $location;

            // Create popup
            $this->_createPopup($options, $popup);

            // Our work here is done
            return;
        }

        // If location is an Address Model
        if ($location instanceof Address) {

            // Get the relevant field
            /** @var AddressField $field */
            $field = $location->getField();

            // Set address and coordinates
            $popup['address'] = $location;
            $popup['coords'] = $location->getCoords();

            // If no marker ID exists
            if (!$popup['markerId']) {
                // If field is known
                if ($field) {
                    // Set marker ID based on element data
                    $popup['markerId'] = "{$location->id}-{$field->handle}";
                } else {
                    // Set marker ID based on merged coordinates
                    $popup['markerId'] = implode(',', $popup['coords']);
                }
            }

            // Create popup
            $this->_createPopup($options, $popup);

            // Our work here is done
            return;
        }

        // If location is a Location Model
        if ($location instanceof Location) {

            // Set address and coordinates
            $popup['coords'] = $location->getCoords();

            // Create popup
            $this->_createPopup($options, $popup);

            // Our work here is done
            return;
        }

        // If location is an Element
        if ($location instanceof Element) {

            // Set both `element` and `entry` (or comparable)
            $elementType = $location::refHandle();
            $popup['element'] = $location;
            $popup[$elementType] = $location;

            // Ensure field option exists
            $options['field'] = ($options['field'] ?? false);

            // Optionally filter by specified field(s)
            if (is_array($options['field'])) {
                $filter = $options['field'];
            } else if (is_string($options['field'])) {
                $filter = [$options['field']];
            } else {
                $filter = false;
            }

            // Get all fields associated with Element
            /** @var FieldLayout $layout */
            $layout = $location->getFieldLayout();
            $fields = $layout->getCustomFields();

            // Loop through all relevant fields
            foreach ($fields as $f) {
                // If filter field was specified but doesn't match, skip it
                if ($filter && !in_array($f->handle, $filter, true)) {
                    continue;
                }
                // If not an Address Field, skip it
                if (!($f instanceof AddressField)) {
                    continue;
                }
                // Get value of Address Field
                $address = $location->{$f->handle};
                // If no Address, skip
                if (!$address) {
                    continue;
                }

                // If address doesn't have valid coordinates, skip
                if (!$address->hasCoords()) {
                    continue;
                }

                // Set address, coordinates, and marker ID
                $popup['address'] = $address;
                $popup['coords'] = $address->getCoords();
                $popup['markerId'] = "{$location->id}-{$f->handle}";

                // Create popup
                $this->_createPopup($options, $popup);

            }

            // Our work here is done
            return;
        }
    }

    /**
     * Add the popup of a single marker.
     *
     * @param array $options
     * @param array $popup
     */
    private function _createPopup(array &$options, array $popup): void
    {
        // If invalid coordinates, bail
        if (!($popup['coords'] ?? false)) {
            return;
        }

        // Get view services
        $view = Craft::$app->getView();

        // Attempt to render the Twig template
        try {

            // Render specified popup template
            $template = $view->renderTemplate($options['popupTemplate'], $popup);

        } catch (\Exception $e) {

            // Get the template root directory
            $root = Craft::$app->getPath()->getSiteTemplatesPath();
            $filepath = $view->resolveTemplate($options['popupTemplate']);
            $filename = str_replace($root, '', $filepath);

            // Render error message template
            $template = $view->renderTemplate('mapbox/maps/popup-error', [
                'filename' => $filename,
                'message' => $e->getMessage(),
            ], View::TEMPLATE_MODE_CP);

        }

        // Set rendered template as popupOptions content
        $options['popupOptions']['content'] = $template;

        // Get the marker ID
        $markerId = $popup['markerId'];

        // Transfer popup to future JS array
        $this->_popups[$markerId] = $options['popupOptions'];
    }

    // ========================================================================= //

    /**
     * Compile all additional JavaScript.
     *
     * @return string
     */
    private function _additionalJs(): string
    {
        // Whether devMode is enabled
        $inDevMode = Craft::$app->getConfig()->getGeneral()->devMode;

        // Whether JavaScript logging is enabled
        $loggingEnabled = (MapboxPlugin::$plugin->getSettings()->enableJsLogging ?? true);

        // Set whether to enable logging to the console
        $logging = ($inDevMode && $loggingEnabled) ? 'true' : 'false';

        // Initialize additional JS data
        $initData = "
window._mbData = {
    logging: {$logging},
    popups: [],
};";

        // Get view service
        $view = Craft::$app->getView();

        // Initialize shared GM data for all maps
        $view->registerJs($initData, $view::POS_HEAD);

        // Initialize unique GM data for each map
        $gmData = '';

        // If popups were specified
        if ($this->_popups) {
            // Initialize list of popups
            $popups = '';
            // Loop through popups
            foreach ($this->_popups as $markerId => $popup) {
                // JSON encode the popup options
                $popupOptions = Json::encode($popup);
                // Append each popup to list
                $popups .= "    '{$markerId}': {$popupOptions},\n";
            }
            // Associate popups with this map
            $gmData .= "\nwindow._mbData.popups['{$this->id}'] = {\n{$popups}};";
        }

        // Return compiled JavaScript
        return $gmData;
    }

}
