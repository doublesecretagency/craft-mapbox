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

use craft\db\Migration;
use ReflectionClass;

/**
 * m230720_223501_add_subfields Migration
 * @since 1.1.0
 */
class m230720_223501_add_subfields extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // If name column doesn't exist, add it
        if (!$this->db->columnExists(Install::MAPBOX_ADDRESSES, 'name')) {
            $this->addColumn(Install::MAPBOX_ADDRESSES, 'name', $this->string()->after('raw'));
        }

        // If neighborhood column doesn't exist, add it
        if (!$this->db->columnExists(Install::MAPBOX_ADDRESSES, 'neighborhood')) {
            $this->addColumn(Install::MAPBOX_ADDRESSES, 'neighborhood', $this->string()->after('zip'));
        }

        // If county column doesn't exist, add it
        if (!$this->db->columnExists(Install::MAPBOX_ADDRESSES, 'county')) {
            $this->addColumn(Install::MAPBOX_ADDRESSES, 'county', $this->string()->after('neighborhood'));
        }

        // If mapboxId column doesn't exist, add it
        if (!$this->db->columnExists(Install::MAPBOX_ADDRESSES, 'mapboxId')) {
            $this->addColumn(Install::MAPBOX_ADDRESSES, 'mapboxId', $this->text()->after('country'));
        }

        // Success
        return true;
    }

    // ========================================================================= //

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Get migration name
        $migration = (new ReflectionClass($this))->getShortName();
        echo "{$migration} cannot be reverted.\n";
        return false;
    }

}
