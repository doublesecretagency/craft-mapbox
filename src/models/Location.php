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

use craft\base\Model;

/**
 * Class Location
 * @since 1.0.0
 */
class Location extends Model
{

    /**
     * @var float|null Longitude of location.
     */
    public ?float $lng = null;

    /**
     * @var float|null Latitude of location.
     */
    public ?float $lat = null;

    // ========================================================================= //

    /**
     * Automatically display coordinates as a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->lng}, {$this->lat}";
    }

    // ========================================================================= //

    /**
     * Whether this location contains a valid set of coordinates.
     *
     * @return bool
     */
    public function hasCoords(): bool
    {
        return (
            is_numeric($this->lng) &&
            is_numeric($this->lat)
        );
    }

    /**
     * Returns a formatted set of coordinates for this location.
     *
     * @return array
     */
    public function getCoords(): array
    {
        return [
            'lng' => $this->lng,
            'lat' => $this->lat,
        ];
    }

    // ========================================================================= //

    /**
     * Calculate the distance between this location and a second location.
     *
     * @param array|Location|null $location
     * @param string $units
     * @return float|null
     */
    public function getDistance(array|Location|null $location = null, string $units = 'miles'): ?float
    {
        // If no location specified, bail
        if (!$location) {
            return null;
        }

        // If starting point has no coordinates, bail
        if (!$this->hasCoords()) {
            return null;
        }

        // Get coordinates of starting point
        $pointA = $this->getCoords();

        // Get coordinates of ending point
        $pointB = $this->_getPointB($location);

        // If ending point has no coordinates, bail
        if (!$pointB) {
            return null;
        }

        // Calculate the distance between the two points
        return $this->_haversinePhp($pointA, $pointB, $units);
    }

    /**
     * Get the ending point to measure distance.
     *
     * @param array|Location $location
     * @return array|null
     */
    private function _getPointB(array|Location $location): ?array
    {
        // If location is a natural set of coordinates, return it as-is
        if (is_array($location) && isset($location['lng'], $location['lat'])) {
            return $location;
        }

        // If ending point is not a Location Model, return false
        if (!($location instanceof Location)) {
            return null;
        }

        // Return coordinates of the Location Model
        return $location->getCoords();
    }

    /**
     * Calculate the distance between two points.
     *
     * @param array $pointA
     * @param array $pointB
     * @param string $units
     * @return float Distance between two points as calculated by the haversine formula.
     */
    private function _haversinePhp(array $pointA, array $pointB, string $units = 'mi'): float
    {
        // Determine radius
//        $radius = ProximitySearchHelper::haversineRadius($units);
        $radius = 0;

        // Set coordinates
        $latA = (float) $pointA['lat'];
        $lngA = (float) $pointA['lng'];
        $latB = (float) $pointB['lat'];
        $lngB = (float) $pointB['lng'];

        // Calculate haversine formula
        return (
            $radius * acos(
                cos(deg2rad($latA)) *
                cos(deg2rad($latB)) *
                cos(deg2rad($lngB) - deg2rad($lngA)) +
                sin(deg2rad($latA)) *
                sin(deg2rad($latB))
            )
        );
    }

}
