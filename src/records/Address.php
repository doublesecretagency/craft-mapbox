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

namespace doublesecretagency\mapbox\records;

use craft\db\ActiveRecord;

/**
 * Class Address
 * @since 1.0.0
 *
 * @property int $id ID of address.
 * @property int $elementId ID of element containing address.
 * @property int $fieldId ID of field containing address.
 * @property string $formatted Properly formatted address according to the Mapbox API.
 * @property string $raw Complete raw JSON address info from Mapbox API.
 * @property string $name Name of place or business.
 * @property string $street1 Street name and number.
 * @property string $street2 Apartment or suite number.
 * @property string $city City.
 * @property string $state State (or province, territory, etc).
 * @property string $zip Zip code (or postal code, etc).
 * @property string $neighborhood Neighborhood.
 * @property string $county County or district (political or administrative).
 * @property string $country Country.
 * @property string $mapboxId A unique ID assigned by the Mapbox API.
 * @property float $distance Distance from another specified point.
 * @property float $lng Longitude of location.
 * @property float $lat Latitude of location.
 * @property int $zoom Zoom level of map.
 */
class Address extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%mapbox_addresses}}';
    }

}
