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

namespace doublesecretagency\mapbox\models;

use craft\base\Model;

/**
 * Class Settings
 * @since 1.0.0
 */
class Settings extends Model
{

    /**
     * @var string|null Mapbox Access Token.
     */
    public ?string $accessToken = null;

    /**
     * @var bool Whether to log JS progress to console. Only relevant when rendering a dynamic map.
     */
    public bool $enableJsLogging = true;

    /**
     * @var int Control the size of map UI elements in Address fields.
     */
    public int $fieldControlSize = 27;

    /**
     * @var array Additional optional parameters for configuring Address fields.
     */
    public array $fieldParams = [];

}
