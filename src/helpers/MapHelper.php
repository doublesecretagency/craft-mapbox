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

namespace doublesecretagency\mapbox\helpers;

use Craft;
use craft\base\Element;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use doublesecretagency\mapbox\fields\AddressField;
use doublesecretagency\mapbox\models\Address;
use doublesecretagency\mapbox\models\Location;

/**
 * Class MapHelper
 * @since 1.0.0
 */
class MapHelper
{

    /**
     * Generate a random ID.
     *
     * @param string|null $prefix
     * @return string
     */
    public static function generateId(?string $prefix = null): string
    {
        // Generate random hash
        $hash = StringHelper::randomString(6);

        // Return new ID (with optional prefix)
        return ($prefix ? "{$prefix}-{$hash}" : $hash);
    }

    // ========================================================================= //

    /**
     * Retrieve all coordinates from a specified set of locations.
     *
     * Coordinates will always be returned inside a parent array,
     * to compensate for Elements with multiple Address Fields.
     *
     * @param array|Element|Location|null $locations
     * @param array $options
     * @return array Collection of coordinate sets
     */
    public static function extractCoords(array|Element|Location|null $locations, array $options = []): array
    {
        // If no locations, return empty array
        if (!$locations) {
            return [];
        }

        // If it's an Address Model, return the coordinates w/ optional ID
        if ($locations instanceof Address) {
            // Get the Address coordinates
            $coords = $locations->getCoords();
            // If field ID exists, get relevant field
            /** @var AddressField $field */
            $field = $locations->fieldId
                ? Craft::$app->getFields()->getFieldById($locations->fieldId)
                : null;
            // If coordinates ID has not already been set
            if (!($coords['id'] ?? false)) {
                // If a field exists
                if ($field) {
                    // Set the marker ID based on element ID and field handle
                    $coords['id'] = "{$locations->id}-{$field->handle}";
                } else {
                    // Generate a marker ID
                    $coords['id'] = MapHelper::generateId('marker');
                }
            }
            // Return the full coordinates
            return [$coords];
        }

        // If it's a Location Model, return the coordinates
        if ($locations instanceof Location) {
            // Get the Location coordinates
            $coords = $locations->getCoords();
            // Set the marker ID based on merged coordinates
            $coords['id'] = ($coords['id'] ?? implode(',', $coords));
            // Return the full coordinates
            return [$coords];
        }

        // If it's a natural set of coordinates, return as-is
        if (is_array($locations) && isset($locations['lat'], $locations['lng'])) {
            // Set the marker ID based on merged coordinates
            $locations['id'] = ($locations['id'] ?? implode(',', $locations));
            // Return the full coordinates
            return [$locations];
        }

        // Force array syntax
        if (!is_array($locations)) {
            $locations = [$locations];
        }

        // No filter by default
        $filter = false;

        // If field value was specified
        if (isset($options['field'])) {
            // Set filter according to type specified
            if (is_array($options['field'])) {
                $filter = $options['field'];
            } else if (is_string($options['field'])) {
                $filter = [$options['field']];
            }
        }

        // Initialize results array
        $results = [];

        // Loop through all locations
        foreach ($locations as $location) {

            // If it's an Address Model, return the coordinates w/ optional ID
            if ($location instanceof Address) {
                // Get the Address coordinates
                $coords = $location->getCoords();
                // Get the relevant field
                /** @var AddressField $field */
                $field = Craft::$app->getFields()->getFieldById($location->fieldId);
                // If a field exists
                if ($field) {
                    // Add marker ID to the coordinates
                    $coords['id'] = ($coords['id'] ?? "{$location->id}-{$field->handle}");
                }
                // Append the full coordinates
                $results[] = $coords;
            }

            // If it's a Location Model, add the coordinates to results
            if ($location instanceof Location) {
                // Get the Location coordinates
                $coords = $location->getCoords();
                // Set the marker ID based on merged coordinates
                $coords['id'] = ($coords['id'] ?? implode(',', $coords));
                // Append the full coordinates
                $results[] = $coords;
            }

            // If it's a natural set of coordinates, add them to results as-is
            if (is_array($location) && isset($location['lng'], $location['lat'])) {
                // Set the marker ID based on merged coordinates
                $location['id'] = ($location['id'] ?? implode(',', $location));
                // Append the full coordinates
                $results[] = $location;
            }

            // If not an Element, skip it
            if (!($location instanceof Element)) {
                continue;
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
                // Add coordinates to results
                if ($address->hasCoords()) {
                    $results[] = array_merge(
                        $address->getCoords(),
                        ['id' => "{$location->id}-{$f->handle}"]
                    );
                }
            }

        }

        // Return final results
        return $results;
    }

}
