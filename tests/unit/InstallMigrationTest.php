<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\db\Migration;
use doublesecretagency\mapbox\migrations\Install;
use doublesecretagency\mapbox\migrations\m230720_223501_add_subfields;
use doublesecretagency\mapbox\migrations\m231009_212501_announcements_v1_1;
use doublesecretagency\mapbox\migrations\m240530_122024_multisite_support;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Source-level tests for the install schema and the migrations that changed it.
 *
 * A fresh install and an upgraded install must end with the same table. That
 * only holds if every column a later migration adds also lives in `Install`,
 * with the same type, so both are checked side by side.
 */
class InstallMigrationTest extends TestCase
{
    private string $install;
    private string $subfields;

    protected function setUp(): void
    {
        $this->install = file_get_contents((new ReflectionClass(Install::class))->getFileName());
        $this->subfields = file_get_contents((new ReflectionClass(m230720_223501_add_subfields::class))->getFileName());
    }

    // ========================================================================= //
    // Table
    // ========================================================================= //

    public function testMigrationsExtendCraftsMigration(): void
    {
        foreach ([Install::class, m230720_223501_add_subfields::class, m231009_212501_announcements_v1_1::class, m240530_122024_multisite_support::class] as $class) {
            $this->assertTrue(is_subclass_of($class, Migration::class), "{$class} is not a Craft migration.");
        }
    }

    public function testReinstallRecoversFromAPartialInstall(): void
    {
        $this->assertMatchesRegularExpression(
            "/if \\(\\\$this->db->tableExists\\(static::MAPBOX_ADDRESSES\\)\\) \\{[\\s\\S]{0,300}return;/",
            $this->install
        );
    }

    public function testUninstallDropsTheTable(): void
    {
        $this->assertStringContainsString('dropTableIfExists(static::MAPBOX_ADDRESSES)', $this->install);
    }

    public function testTheTableNameIsDefinedOnce(): void
    {
        // The multisite migration and the install share this constant
        $this->assertSame('{{%mapbox_addresses}}', Install::MAPBOX_ADDRESSES);
        $this->assertStringNotContainsString("'{{%mapbox_addresses}}', [", $this->install);
    }

    // ========================================================================= //
    // Columns
    // ========================================================================= //

    /**
     * @dataProvider columnProvider
     */
    public function testColumnType(string $column, string $definition): void
    {
        $this->assertMatchesRegularExpression(
            "/'{$column}'\\s*=>\\s*" . preg_quote($definition, '/') . ',/',
            $this->install,
            "Install should define `{$column}` as {$definition}."
        );
    }

    public static function columnProvider(): array
    {
        $string = '$this->string()';

        return [
            'id' => ['id', '$this->primaryKey()'],
            'elementId' => ['elementId', '$this->integer()->notNull()'],
            'siteId' => ['siteId', '$this->integer()->notNull()'],
            'fieldId' => ['fieldId', '$this->integer()->notNull()'],
            'formatted' => ['formatted', $string],
            'raw' => ['raw', '$this->text()'],
            'name' => ['name', $string],
            'street1' => ['street1', $string],
            'street2' => ['street2', $string],
            'city' => ['city', $string],
            'state' => ['state', $string],
            'zip' => ['zip', $string],
            'neighborhood' => ['neighborhood', $string],
            'county' => ['county', $string],
            'country' => ['country', $string],
            'mapboxId' => ['mapboxId', '$this->text()'],
            'lng' => ['lng', '$this->decimal(12, 8)'],
            'lat' => ['lat', '$this->decimal(12, 8)'],
            'zoom' => ['zoom', '$this->decimal(4, 2)'],
            'dateCreated' => ['dateCreated', '$this->dateTime()->notNull()'],
            'dateUpdated' => ['dateUpdated', '$this->dateTime()->notNull()'],
            'uid' => ['uid', '$this->uid()'],
        ];
    }

    // ========================================================================= //
    // Indexes and foreign keys
    // ========================================================================= //

    /**
     * @dataProvider indexedColumnProvider
     */
    public function testColumnIsIndexed(string $column): void
    {
        $this->assertStringContainsString("createIndex(null, static::MAPBOX_ADDRESSES, ['{$column}'])", $this->install);
    }

    /**
     * @dataProvider indexedColumnProvider
     */
    public function testColumnCascadesOnDelete(string $column, string $table): void
    {
        // Deleting an element or a field must take its addresses with it.
        $this->assertMatchesRegularExpression(
            "/addForeignKey\\(null, static::MAPBOX_ADDRESSES, \\['{$column}'\\],\\s*Table::{$table},\\s*\\['id'\\],\\s*'CASCADE'\\)/",
            $this->install
        );
    }

    public static function indexedColumnProvider(): array
    {
        return [
            'elementId' => ['elementId', 'ELEMENTS'],
            'siteId' => ['siteId', 'SITES'],
            'fieldId' => ['fieldId', 'FIELDS'],
        ];
    }

    /**
     * @dataProvider multisiteIndexProvider
     */
    public function testInstallHasTheMultisiteIndexes(string $columns, bool $unique): void
    {
        // A fresh install must end with the same indexes the multisite migration adds
        $pattern = '/createIndex\\(null, static::MAPBOX_ADDRESSES, ' . preg_quote($columns, '/') . ($unique ? ', true' : '') . '\\);/';
        $this->assertMatchesRegularExpression($pattern, $this->install);
    }

    public static function multisiteIndexProvider(): array
    {
        return [
            'siteId, fieldId' => ["['siteId', 'fieldId']", false],
            'elementId, siteId' => ["['elementId', 'siteId']", false],
            'elementId, fieldId' => ["['elementId', 'fieldId']", false],
            'unique elementId, siteId, fieldId' => ["['elementId', 'siteId', 'fieldId']", true],
        ];
    }

    // ========================================================================= //
    // m230720_223501_add_subfields
    // ========================================================================= //

    /**
     * @dataProvider addedColumnProvider
     */
    public function testSubfieldMigrationAddsTheColumnSafely(string $column, string $type): void
    {
        $this->assertStringContainsString("if (!\$this->db->columnExists(\$table, '{$column}'))", $this->subfields);
        $this->assertStringContainsString("addColumn(\$table, '{$column}', \$this->{$type}()", $this->subfields);
    }

    /**
     * @dataProvider addedColumnProvider
     */
    public function testInstallAlreadyHasTheAddedColumnWithTheSameType(string $column, string $type): void
    {
        $this->assertMatchesRegularExpression("/'{$column}'\\s*=>\\s*\\\$this->{$type}\\(\\)/", $this->install);
    }

    public static function addedColumnProvider(): array
    {
        return [
            'name' => ['name', 'string'],
            'neighborhood' => ['neighborhood', 'string'],
            'county' => ['county', 'string'],
            'mapboxId' => ['mapboxId', 'text'],
        ];
    }

    public function testSubfieldMigrationTargetsTheAddressTable(): void
    {
        $this->assertStringContainsString("\$table = '{{%mapbox_addresses}}';", $this->subfields);
    }

    public function testNoMigrationIsNewerThanTheSchemaVersionAllows(): void
    {
        // Every migration on disk is one the schema version knows about.
        $files = array_map('basename', glob(dirname(__DIR__, 2) . '/src/migrations/m*.php'));
        sort($files);

        $this->assertSame([
            'm230720_223501_add_subfields.php',
            'm231009_212501_announcements_v1_1.php',
            'm240530_122024_multisite_support.php',
        ], $files);
    }
}
