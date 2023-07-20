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
        // Table of all Addresses
        $table = '{{%mapbox_addresses}}';

        // If name column doesn't exist, add it
        if (!$this->db->columnExists($table, 'name')) {
            $this->addColumn($table, 'name', $this->string()->after('raw'));
        }

        // If neighborhood column doesn't exist, add it
        if (!$this->db->columnExists($table, 'neighborhood')) {
            $this->addColumn($table, 'neighborhood', $this->string()->after('zip'));
        }

        // If county column doesn't exist, add it
        if (!$this->db->columnExists($table, 'county')) {
            $this->addColumn($table, 'county', $this->string()->after('neighborhood'));
        }

        // If mapboxId column doesn't exist, add it
        if (!$this->db->columnExists($table, 'mapboxId')) {
            $this->addColumn($table, 'mapboxId', $this->text()->after('country'));
        }

        // Success
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230720_223501_add_subfields cannot be reverted.\n";
        return false;
    }

}
