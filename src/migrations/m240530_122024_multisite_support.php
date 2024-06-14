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
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\i18n\Translation;
use Exception;
use ReflectionClass;
use yii\base\NotSupportedException;

/**
 * m240530_122024_multisite_support Migration
 * @since 1.2.0
 */
class m240530_122024_multisite_support extends Migration
{

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function safeUp(): bool
    {
        // Add new columns
        $this->_newColumns();

        // Add new column indexes
        $this->_newIndexes();

        // Add new foreign keys
        $this->_newForeignKeys();

        // Populate new columns with existing data
        $this->_populateData();

        // Make new column not nullable, AFTER the column has been populated
        $this->alterColumn(Install::MAPBOX_ADDRESSES, 'siteId', $this->integer()->notNull());

        // Post an announcement
        $this->_announcement();

        // Success
        return true;
    }

    // ========================================================================= //

    /**
     * Add new columns.
     * @throws NotSupportedException
     */
    private function _newColumns(): void
    {
        // If column doesn't exist, add it
        if (!$this->db->columnExists(Install::MAPBOX_ADDRESSES, 'siteId')) {
            $this->addColumn(Install::MAPBOX_ADDRESSES, 'siteId', $this->integer()->after('elementId'));
        }
    }

    /**
     * Add new column indexes.
     */
    private function _newIndexes(): void
    {
        $this->createIndex(null, Install::MAPBOX_ADDRESSES, ['siteId']);
        $this->createIndex(null, Install::MAPBOX_ADDRESSES, ['siteId', 'fieldId']);
        $this->createIndex(null, Install::MAPBOX_ADDRESSES, ['elementId', 'siteId']);
        $this->createIndex(null, Install::MAPBOX_ADDRESSES, ['elementId', 'fieldId']);
        $this->createIndex(null, Install::MAPBOX_ADDRESSES, ['elementId', 'siteId', 'fieldId'], true);
    }

    /**
     * Add new foreign keys.
     */
    private function _newForeignKeys(): void
    {
        $this->addForeignKey(null, Install::MAPBOX_ADDRESSES, ['siteId'], Table::SITES, ['id'], 'CASCADE');
    }

    // ========================================================================= //

    /**
     * Populate new columns with existing data.
     * @throws Exception
     */
    private function _populateData(): void
    {
        // Get all existing Address data
        $rows = (new Query())
            ->select('*')
            ->from(Install::MAPBOX_ADDRESSES)
            ->orderBy('[[id]]')
            ->all();

        // If no existing rows, bail
        if (!$rows) {
            return;
        }

        // Get columns
        $columns = array_keys($rows[0]);

        // Get sites service
        $sites = Craft::$app->getSites();

        // Get the primary site ID
        $primarySiteId = $sites->getPrimarySite()->id;

        // Get IDs from all sites
        $siteIds = ArrayHelper::getColumn($sites->getAllSites(), 'id');

        // Sort IDs numerically
        sort($siteIds);

        // Set date updated to right now
        $dateUpdated = DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s');

        // Loop over all available sites
        foreach ($siteIds as $siteId) {

            // Skip the primary site (it will be handled later via `update`)
            if ($siteId === $primarySiteId) {
                continue;
            }

            // Adjust row accordingly
            foreach ($rows as &$row) {
                $row['id'] = null;                  // Allow fresh ID
                $row['siteId'] = $siteId;           // Specify each site ID
                $row['dateUpdated'] = $dateUpdated; // Update date updated
                $row['uid'] = StringHelper::UUID(); // Generate new UUID
            }

            // Prevent complications
            unset($row);

            // Insert all rows for this site
            $this->batchInsert(Install::MAPBOX_ADDRESSES, $columns, $rows);

        }

        // Set the site ID of all the original Addresses
        $this->update(
            Install::MAPBOX_ADDRESSES,
            ['siteId' => $primarySiteId],
            ['siteId' => null]
        );
    }

    // ========================================================================= //

    /**
     * Post an announcement.
     */
    private function _announcement(): void
    {
        // Post announcement
        Craft::$app->getAnnouncements()->push(
            Translation::prep('mapbox', 'NEW: Translatable Address Fields'),
            Translation::prep('mapbox', 'For projects with [multiple sites]({url}), each site can now store a different Address field value.', [
                'url' => 'https://plugins.doublesecretagency.com/mapbox/address-field/multisite-support/',
            ]),
            'mapbox'
        );
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
