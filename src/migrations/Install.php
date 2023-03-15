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

/**
 * Installation Migration
 * @since 1.0.0
 */
class Install extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp(): void
    {
        // If the table already exists, move on
        // (gracefully recover from a previous failed migration attempt)
        if ($this->db->tableExists('{{%mapbox_addresses}}')) {
            $message = "The `mapbox_addresses` table already exists. We may be recovering from a previously failed migration.";
            Craft::warning($message, __METHOD__);
            return;
        }

        // Install and configure table
        $this->_createTables();
        $this->_createIndexes();
        $this->_addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): void
    {
        $this->dropTableIfExists('{{%mapbox_addresses}}');
    }

    /**
     * Creates the tables.
     */
    private function _createTables(): void
    {
        $this->createTable('{{%mapbox_addresses}}', [
            'id'          => $this->primaryKey(),
            'elementId'   => $this->integer()->notNull(),
            'fieldId'     => $this->integer()->notNull(),
            'formatted'   => $this->string(),
            'raw'         => $this->text(),
            'street1'     => $this->string(),
            'street2'     => $this->string(),
            'city'        => $this->string(),
            'state'       => $this->string(),
            'zip'         => $this->string(),
            'country'     => $this->string(),
            'lng'         => $this->decimal(12, 8),
            'lat'         => $this->decimal(12, 8),
            'zoom'        => $this->tinyInteger(2),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
        ]);
    }

    /**
     * Creates the indexes.
     */
    private function _createIndexes(): void
    {
        $this->createIndex(null, '{{%mapbox_addresses}}', ['elementId']);
        $this->createIndex(null, '{{%mapbox_addresses}}', ['fieldId']);
    }

    /**
     * Adds the foreign keys.
     */
    private function _addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%mapbox_addresses}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%mapbox_addresses}}', ['fieldId'],   '{{%fields}}',   ['id'], 'CASCADE');
    }

}
