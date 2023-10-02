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

namespace doublesecretagency\mapbox\migrations;

use Craft;
use craft\db\Migration;
use craft\i18n\Translation;

/**
 * m231009_212501_announcements_v1_1 Migration
 * @since 1.1.0
 */
class m231009_212501_announcements_v1_1 extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Get announcements services
        $announcements = Craft::$app->getAnnouncements();

        // New & Improved Address Lookups
        $announcements->push(
            Translation::prep('mapbox', 'New & Improved Address Lookups'),
            Translation::prep('mapbox', 'The updated Address field returns much better lookup results, and now lets you search by store names.'),
            'mapbox'
        );

        // Custom Marker Icons
        $announcements->push(
            Translation::prep('mapbox', 'Custom Marker Icons'),
            Translation::prep('mapbox', 'It\'s now simple to use [custom marker icons]({docsUrl}) on a dynamic map.', [
                'docsUrl' => 'https://plugins.doublesecretagency.com/mapbox/guides/setting-marker-icons/#image-as-marker-icon',
            ]),
            'mapbox'
        );

        // Success
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231009_212501_announcements_v1_1 cannot be reverted.\n";
        return false;
    }

}
