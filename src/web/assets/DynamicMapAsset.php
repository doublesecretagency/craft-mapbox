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
use doublesecretagency\mapbox\MapboxPlugin;

/**
 * Loads the plugin's own JavaScript for dynamic maps.
 *
 * @since 2.2.0
 */
class DynamicMapAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Publish the folder of dynamic map scripts
        $this->sourcePath = '@doublesecretagency/mapbox/resources';

        // Whether to use minified JavaScript files (settings may not exist during a Cloud build)
        $minifyJsFiles = (MapboxPlugin::getInstance()?->getSettings()->minifyJsFiles ?? false);

        // Optionally use minified files
        $min = ($minifyJsFiles ? 'min.' : '');

        // Load JS files
        $this->js = [
            "js/mapbox.{$min}js",
            "js/dynamicmap.{$min}js",
        ];

        // Load in the head, right after the Mapbox API
        $this->jsOptions = [
            'position' => View::POS_HEAD,
        ];
    }

}
