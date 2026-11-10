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
 * `mapbox_addresses` with one `INSERT ... SELECT`. Every column in the copy
 * list must exist in both tables, or the whole statement fails and the field
 * converts with no data.
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
        $this->assertStringContainsString('INSERT INTO [[mapbox_addresses]]', $this->source);
        $this->assertStringContainsString('FROM [[googlemaps_addresses]]', $this->source);
    }

    public function testCopiesOnlyThisFieldsRows(): void
    {
        $this->assertStringContainsString('WHERE [[fieldId]] = :fieldId', $this->source);
        $this->assertStringContainsString("bindValues([':fieldId' => \$field->id])", $this->source);
    }

    public function testSkipsRowsAlreadyCopied(): void
    {
        // Converting back and forth must not duplicate rows.
        $this->assertMatchesRegularExpression(
            '/NOT EXISTS[\s\S]*?\[\[mapbox_addresses\]\]\.\[\[uid\]\] = \[\[googlemaps_addresses\]\]\.\[\[uid\]\]/',
            $this->source
        );
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
            'dateCreated', 'dateUpdated', 'uid',
        ];
        return array_combine($columns, array_map(static fn(string $c): array => [$c], $columns));
    }

    public function testTheCopyListIsExactlyTheKnownColumns(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(static::copiedColumnProvider()), $this->_columns());
    }

    /**
     * Google Maps has no neighborhood and no Mapbox ID, so copying either
     * would fail the whole statement.
     */
    public function testMapboxOnlyColumnsAreNotCopied(): void
    {
        $this->assertNotContains('neighborhood', $this->_columns());
        $this->assertNotContains('mapboxId', $this->_columns());
    }

    public function testColumnNamesAreQuoted(): void
    {
        $this->assertStringContainsString("\$columns = '[['.implode(']],[[', \$columns).']]';", $this->source);
    }
}
