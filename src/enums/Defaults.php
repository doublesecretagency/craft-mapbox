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

namespace doublesecretagency\mapbox\enums;

/**
 * Class Defaults
 * @since 1.0.0
 */
abstract class Defaults
{

    /**
     * Default coordinates. (Bermuda Triangle)
     */
    public const COORDINATES = [
        'lat' => 32.3113966,
        'lng' => -64.7527469,
        'zoom' => 6
    ];

    /**
     * Default subfield configuration.
     */
    public const SUBFIELDCONFIG = [
        [
            'handle'       => 'street1',
            'label'        => 'Street Address',
            'width'        => 100,
            'enabled'      => true,
            'autocomplete' => true,
            'required'     => false
        ],
        [
            'handle'       => 'street2',
            'label'        => 'Apartment or Suite',
            'width'        => 100,
            'enabled'      => true,
            'autocomplete' => false,
            'required'     => false
        ],
        [
            'handle'       => 'city',
            'label'        => 'City',
            'width'        => 50,
            'enabled'      => true,
            'autocomplete' => false,
            'required'     => false
        ],
        [
            'handle'       => 'state',
            'label'        => 'State',
            'width'        => 15,
            'enabled'      => true,
            'autocomplete' => false,
            'required'     => false
        ],
        [
            'handle'       => 'zip',
            'label'        => 'Zip Code',
            'width'        => 35,
            'enabled'      => true,
            'autocomplete' => false,
            'required'     => false
        ],
        [
            'handle'       => 'country',
            'label'        => 'Country',
            'width'        => 100,
            'enabled'      => true,
            'autocomplete' => false,
            'required'     => false
        ],
    ];

}
