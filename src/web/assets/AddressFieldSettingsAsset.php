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
 * Class AddressFieldSettingsAsset
 * @since 1.0.0
 */
class AddressFieldSettingsAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->sourcePath = '@doublesecretagency/mapbox/web/assets/dist';
        $this->depends = [
            AddressFieldAsset::class,
        ];

        $this->js = [
            'js/Sortable.min.js',
            'js/address-settings.js',
        ];
    }

}
