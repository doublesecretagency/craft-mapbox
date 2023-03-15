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

/**
 * Class JsApiAsset
 * @since 1.0.0
 */
class JsApiAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->sourcePath = '@doublesecretagency/mapbox/resources';
        $this->depends = [
            MapboxAsset::class,
        ];

        $this->js = [
            'js/mapbox.js',
            'js/dynamicmap.js',
        ];
    }

}
