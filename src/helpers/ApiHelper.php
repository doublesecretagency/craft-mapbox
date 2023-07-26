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

use craft\helpers\App;
use craft\helpers\UrlHelper;
use doublesecretagency\mapbox\MapboxPlugin;
use doublesecretagency\mapbox\models\Settings;

/**
 * Class ApiHelper
 * @since 1.0.0
 */
class ApiHelper
{

    /**
     * @var string|null Mapbox API access token.
     */
    private static ?string $_accessToken = null;

    // ========================================================================= //

    /**
     * Get the Mapbox Access Token.
     *
     * @return string Mapbox Access Token.
     */
    public static function getAccessToken(): string
    {
        /** @var Settings $settings */
        $settings = MapboxPlugin::$plugin->getSettings();
        // Only load once
        if (null === static::$_accessToken) {
            static::$_accessToken = App::parseEnv($settings->accessToken);
        }
        // Return key
        return trim(static::$_accessToken);
    }

    /**
     * Set the Mapbox Access Token.
     *
     * @param string $token
     * @return string Mapbox Access Token.
     */
    public static function setAccessToken(string $token): string
    {
        return static::$_accessToken = $token;
    }

    // ========================================================================= //

    /**
     * Compile the URL for pinging a Mapbox API endpoint.
     *
     * @param string $service
     * @param array $params
     * @return string|null The fully compiled URL.
     */
    public static function getApiUrl(string $service, array $params = []): ?string
    {
        // Set base URL
        $apiUrl = 'https://api.mapbox.com/';

        // Complete endpoint based on specified service
        switch ($service) {
            case 'maps':
                // Maps API
                $apiUrl .= 'mapbox-gl-js/v2.13.0/mapbox-gl.js';
                break;
            case 'search':
                // Search API
                $apiUrl .= 'search-js/v1.0.0-beta.17/core.js';
                break;
            case 'language':
                // Plugin for improved language support
                $apiUrl .= 'mapbox-gl-js/plugins/mapbox-gl-language/v1.0.0/mapbox-gl-language.js';
                break;
//            case 'navigation':
//                $baseUrl .= '';
//                break;
            default:
                // Something went wrong
                return null;
        }

//        $params = [
//            'access_token' => Mapbox::getAccessToken()
//        ];

        // Return the fully compiled URL
        return UrlHelper::urlWithParams($apiUrl, $params);
    }

}
