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
            'https://api.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.css',
            'css/address.css',
        ];

        $this->js = [
            Mapbox::getApiUrl('maps'),
            Mapbox::getApiUrl('search'),
            Mapbox::getApiUrl('language'),
            'js/address.js',
        ];
    }

}
