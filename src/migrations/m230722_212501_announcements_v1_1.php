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
 * m230722_212501_announcements_v1_1 Migration
 * @since 1.1.0
 */
class m230722_212501_announcements_v1_1 extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Get announcements services
        $announcements = Craft::$app->getAnnouncements();

        // New Address Subfields
        $announcements->push(
            Translation::prep('mapbox', 'New subfields for Mapbox Address fields'),
            Translation::prep('mapbox', 'The new subfields [`name`]({name}), [`neighborhood`]({neighborhood}), [`county`]({county}), and [`mapboxId`]({mapboxId}) have been added to Address fields and models.', [
                'name'          => 'https://plugins.doublesecretagency.com/mapbox/models/address-model/#name',
                'neighborhood'  => 'https://plugins.doublesecretagency.com/mapbox/models/address-model/#neighborhood',
                'county'        => 'https://plugins.doublesecretagency.com/mapbox/models/address-model/#county',
                'mapboxId'      => 'https://plugins.doublesecretagency.com/mapbox/models/address-model/#mapboxid',
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
        echo "m230722_212501_announcements_v1_1 cannot be reverted.\n";
        return false;
    }

}
