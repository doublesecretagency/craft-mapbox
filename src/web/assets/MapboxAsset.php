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
use craft\web\View;
use doublesecretagency\mapbox\helpers\Mapbox;

/**
 * Class MapboxAsset
 * @since 1.0.0
 */
class MapboxAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->css = [
            'https://api.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.css'
        ];

        // Load Mapbox API
        $this->js = [
            Mapbox::getApiUrl('maps')
        ];

        $this->jsOptions = [
            'position' => View::POS_HEAD,
        ];
    }

}
