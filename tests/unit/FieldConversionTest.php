<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\MapboxPlugin;
use doublesecretagency\mapbox\migrations\Install;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Source-level tests for converting a Google Maps Address field into a Mapbox one.
 *
 * When a field's type changes from the Google Maps Address field to this one,
 * the plugin copies that field's rows from `googlemaps_addresses` into
 * `mapbox_addresses`, one site at a time. Every column in the copy list must
 * exist in both tables, or the insert fails and the field converts with no data.
 */
class FieldConversionTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        $source = file_get_contents((new ReflectionClass(MapboxPlugin::class))->getFileName());
        $start = strpos($source, 'private function _manageFieldTypeConversions');
        $end = strpos($source, 'private function _postInstallRedirect');

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $this->source = substr($source, $start, $end - $start);
    }

    /**
     * Get the copied column list.
     */
    private function _columns(): array
    {
        $this->assertMatchesRegularExpression('/\$columns = \[([\s\S]*?)\];/', $this->source);
        preg_match('/\$columns = \[([\s\S]*?)\];/', $this->source, $match);
        preg_match_all("/'(\\w+)'/", $match[1], $columns);

        return $columns[1];
    }

    // ========================================================================= //
    // When it runs
    // ========================================================================= //

    public function testOnlyRunsWhenGoogleMapsIsEnabled(): void
    {
        $this->assertStringContainsString("if (!Craft::\$app->getPlugins()->isPluginEnabled('google-maps'))", $this->source);
    }

    public function testListensForProjectConfigItemUpdates(): void
    {
        $this->assertStringContainsString('ProjectConfig::EVENT_UPDATE_ITEM', $this->source);
    }

    public function testOnlyConvertsFromTheGoogleMapsField(): void
    {
        $this->assertStringContainsString("'doublesecretagency\\googlemaps\\fields\\AddressField'", $this->source);
    }

    public function testOnlyConvertsIntoTheMapboxField(): void
    {
        $this->assertStringContainsString("'doublesecretagency\\mapbox\\fields\\AddressField'", $this->source);
    }

    // ========================================================================= //
    // What it copies
    // ========================================================================= //

    public function testCopiesFromGoogleMapsIntoMapbox(): void
    {
        $this->assertStringContainsString("->from('{{%googlemaps_addresses}}')", $this->source);
        $this->assertStringContainsString('Db::batchInsert(Install::MAPBOX_ADDRESSES,', $this->source);
    }

    public function testCopiesOnlyThisFieldsRows(): void
    {
        $this->assertStringContainsString('self::_copyGoogleMapsAddresses((int) $field->id);', $this->source);
        $this->assertStringContainsString("->where(['fieldId' => \$fieldId])", $this->source);
    }

    /**
     * Converting back and forth, or converting over Mapbox rows saved since,
     * must not hit the unique (elementId, siteId, fieldId) index. Matching on
     * uid alone let a different row for the same slot through, and the insert
     * failed the whole project config apply.
     */
    public function testSkipsSitesWhichAlreadyHaveAnAddress(): void
    {
        $this->assertStringContainsString("->select(['elementId', 'siteId'])->from(Install::MAPBOX_ADDRESSES)->where(['fieldId' => \$fieldId])", $this->source);
        $this->assertStringContainsString('if (isset($taken["{$elementId}:{$siteId}"])) {', $this->source);
        $this->assertStringContainsString('$taken["{$elementId}:{$siteId}"] = true;', $this->source);
        $this->assertStringNotContainsString('[[uid]] = ', $this->source);
    }

    /**
     * One Google Maps row can become several Mapbox rows, so the source's uid
     * isn't copied. Craft's batchInsert adds a fresh one per row.
     */
    public function testEveryCopyGetsAFreshUid(): void
    {
        $this->assertNotContains('uid', $this->_columns());
        $this->assertStringContainsString('$rows[] = [...$values, $siteId];', $this->source);
        $this->assertStringNotContainsString("'uid'", $this->source);
    }

    /**
     * The copy runs once, when the field type changes. A copy that failed
     * halfway would never resume, so it's all or nothing, in batches.
     */
    public function testCopiesInOneTransactionInBatches(): void
    {
        $this->assertStringContainsString('$db->transaction(static function () use (', $this->source);
        $this->assertStringContainsString('foreach (Db::batch($addresses) as $batch) {', $this->source);
        $this->assertStringContainsString("Db::batchInsert(Install::MAPBOX_ADDRESSES, [...\$columns, 'siteId'], \$rows, \$db);", $this->source);
        $this->assertStringNotContainsString('->exists(', $this->source);
    }

    /**
     * Table names must carry the prefix, or a prefixed install converts nothing.
     */
    public function testTableNamesUseThePrefix(): void
    {
        $this->assertStringNotContainsString('[[mapbox_addresses]]', $this->source);
        $this->assertStringNotContainsString('[[googlemaps_addresses]]', $this->source);
        $this->assertStringContainsString("columnExists('{{%googlemaps_addresses}}', 'siteId')", $this->source);
    }

    /**
     * @dataProvider copiedColumnProvider
     */
    public function testEveryCopiedColumnExistsInTheMapboxTable(string $column): void
    {
        $install = file_get_contents((new ReflectionClass(Install::class))->getFileName());

        $this->assertContains($column, $this->_columns());
        $this->assertMatchesRegularExpression("/'{$column}'\\s*=>\\s*\\\$this->/", $install);
    }

    public static function copiedColumnProvider(): array
    {
        $columns = [
            'elementId', 'fieldId', 'formatted', 'raw', 'name', 'street1', 'street2',
            'city', 'state', 'zip', 'county', 'country', 'lng', 'lat', 'zoom',
            'dateCreated', 'dateUpdated',
        ];
        return array_combine($columns, array_map(static fn(string $c): array => [$c], $columns));
    }

    public function testTheCopyListIsExactlyTheKnownColumns(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(static::copiedColumnProvider()), $this->_columns());
    }

    /**
     * Google Maps has no Mapbox ID, so copying it would fail the insert.
     */
    public function testMapboxOnlyColumnsAreNotCopied(): void
    {
        $this->assertNotContains('mapboxId', $this->_columns());
    }

    /**
     * Google Maps added a neighborhood in 2024 (m240320_131520_add_more_subfields).
     * Copy it when the table has it; older tables don't, and listing it
     * unconditionally would fail the insert.
     */
    public function testNeighborhoodIsCopiedOnlyWhenGoogleMapsHasIt(): void
    {
        $this->assertNotContains('neighborhood', $this->_columns());
        $this->assertStringContainsString("if (\$db->columnExists('{{%googlemaps_addresses}}', 'neighborhood')) {\n            \$columns[] = 'neighborhood';", $this->source);
    }

    /**
     * Every Mapbox row needs a site. Google Maps 4.6+ and 5.1+ store one per
     * row, which carries over. Older versions shared one row across every
     * site, so it's copied to each site of its element (or the primary site
     * when the element is on none). Reads now filter by site, so a row left
     * on the primary site alone would leave every other site empty.
     */
    public function testEveryCopiedRowGetsASite(): void
    {
        $this->assertNotContains('siteId', $this->_columns(), 'siteId must not be copied unconditionally.');
        $this->assertStringContainsString("->select(\$hasSites ? [...\$columns, 'siteId'] : \$columns)", $this->source);
        $this->assertStringContainsString("\$siteIds = (\$elementSites[\$elementId] ?? [\$primarySiteId]);", $this->source);
        // A row with its own site is copied only if the element exists on that site
        $this->assertStringContainsString("\$siteIds = array_intersect(\$siteIds, [(int) \$address['siteId']]);", $this->source);
        $this->assertStringContainsString("->from(Table::ELEMENTS_SITES)", $this->source);
    }
}
