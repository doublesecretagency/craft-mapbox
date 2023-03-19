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
use craft\web\View;
use doublesecretagency\mapbox\models\DynamicMap;
use doublesecretagency\mapbox\models\Location;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class Mapbox
 * @since 1.0.0
 */
class Mapbox
{

    /**
     * @var array An internally managed collection of Dynamic Maps.
     */
    private static array $_maps = [];

    // ========================================================================= //
    // Dynamic Maps
    // https://plugins.doublesecretagency.com/mapbox/dynamic-maps/
    // ========================================================================= //

    /**
     * Get a list of the JavaScript assets necessary for displaying Dynamic Maps.
     *
     * @param string $service Selected Mapbox API service.
     * @param array $params Optional parameters for the Mapbox API.
     * @return string[] Collection of JS files required to display maps.
     */
    public static function getAssets(string $service, array $params = []): array
    {
        // Get asset manager
        $manager = Craft::$app->getAssetManager();
        $assets = '@doublesecretagency/mapbox/resources';

        // Link to API URL
        $files = [self::getApiUrl($service, $params)];

        // Append both JS files required by plugin
        $files[] = $manager->getPublishedUrl($assets, true, 'js/mapbox.js');
        $files[] = $manager->getPublishedUrl($assets, true, 'js/dynamicmap.js');

        // Return list of files
        return $files;
    }

    /**
     * Load the JavaScript & CSS assets necessary for displaying Dynamic Maps.
     *
     * @param string $service Selected Mapbox API service.
     * @param array $params Optional parameters for the Mapbox API.
     * @throws InvalidConfigException
     */
    public static function loadAssets(string $service, array $params = []): void
    {
        // Get view service
        $view = Craft::$app->getView();

        // Get all required assets
        $assets = static::getAssets($service, $params);

        // Load each JS file
        foreach ($assets as $file) {
            $view->registerJsFile($file, ['position' => View::POS_HEAD]);
        }

        // Get access token
        $accessToken = getenv('MAPBOX_ACCESSTOKEN');

        // Load access token
        $view->registerJs("window.mapboxAccessToken='{$accessToken}'", View::POS_HEAD);

        // Load CSS file
        $view->registerCssFile('https://api.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.css');
    }

    // ========================================================================= //

    /**
     * Create a new Dynamic Map object.
     *
     * @param array|Element|Location|null $locations
     * @param array $options
     * @return DynamicMap
     */
    public static function map(array|Element|Location|null $locations = [], array $options = []): DynamicMap
    {
        // Create a new map object
        $map = new DynamicMap($locations, $options);

        // Store map object for future reference
        static::$_maps[$map->id] = $map;

        // Return the map object
        return $map;
    }

    /**
     * Get an existing Dynamic Map object.
     *
     * @param string $mapId
     * @return DynamicMap|null
     * @throws Exception
     */
    public static function getMap(string $mapId): ?DynamicMap
    {
        // Get existing map object
        $map = (static::$_maps[$mapId] ?? false);

        // If no map object exists, throw an error
        if (!$map) {
            throw new Exception("Encountered an error using the `getMap` method. The map \"{$mapId}\" does not exist.");
        }

        // Return the map object
        return $map;
    }

    // ========================================================================= //
    // API Service
    // https://plugins.doublesecretagency.com/mapbox/helper/api/
    // ========================================================================= //

    /**
     * Get a Mapbox API URL.
     *
     * @param string $service
     * @param array $params
     * @return string|null The fully compiled URL.
     */
    public static function getApiUrl(string $service, array $params = []): ?string
    {
        return ApiHelper::getApiUrl($service, $params);
    }

    // ========================================================================= //

    /**
     * Get the Mapbox Access Token.
     *
     * @return string
     */
    public static function getAccessToken(): string
    {
        return ApiHelper::getAccessToken();
    }

    /**
     * Set the Mapbox Access Token.
     *
     * @param string $token
     * @return string
     */
    public static function setAccessToken(string $token): string
    {
        return ApiHelper::setAccessToken($token);
    }

}
