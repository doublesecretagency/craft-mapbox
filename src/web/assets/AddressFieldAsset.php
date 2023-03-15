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

namespace doublesecretagency\mapbox\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;
use doublesecretagency\mapbox\MapboxPlugin;
use doublesecretagency\mapbox\helpers\Mapbox;

/**
 * Class AddressFieldAsset
 * @since 1.0.0
 */
class AddressFieldAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->sourcePath = '@doublesecretagency/mapbox/web/assets/dist';
        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        $this->css = [
            'css/address.css',
        ];

        $this->js = [
            $this->_getApiUrl(),
            'js/address.js',
        ];
    }

    /**
     * Generate a fully compiled URL for the Mapbox API.
     *
     * @return string
     */
    private function _getApiUrl(): string
    {
        // Required API URL configuration options
        $params = [
//            'libraries' => 'places',
//            'callback' => 'initAddressField',
        ];

        // Get optional field parameters
        $fieldParams = MapboxPlugin::$plugin->getSettings()->fieldParams;

        // If field parameters are specified, append them
        if ($fieldParams && is_array($fieldParams)) {
            $params = array_merge($params, $fieldParams);
        }

        // Return the fully compiled API URL
        return Mapbox::getApiUrl('search', $params);
    }

}
