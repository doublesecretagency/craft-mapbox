<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\enums\Defaults;
use doublesecretagency\mapbox\models\Address;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * The address keys, checked against every place they are repeated by hand.
 *
 * Adding or renaming an Address subfield means touching about ten places: the
 * install migration, the record docblock, the model, the field's save, load
 * and search-keyword methods, the data handed to Vue, `Defaults`, and the
 * Pinia store that maps a Mapbox search result into the subfields. Nothing
 * fails when one is missed; the value just never saves, never loads, or never
 * gets filled in. This file is the guard for exactly that.
 *
 * Each site is compared as a SET against the subset of keys it should carry,
 * so a missing key and an unexpected one both fail, and the message names it.
 */
class AddressContractParityTest extends TestCase
{
    /** The ten subfields, in `Defaults` order. */
    private const SUBFIELDS = [
        'name', 'street1', 'street2', 'city', 'state', 'zip',
        'neighborhood', 'county', 'country', 'mapboxId',
    ];

    /** Data that comes from the Mapbox result rather than a subfield. */
    private const META = ['formatted', 'raw'];

    /** The coordinate set. */
    private const COORDS = ['lng', 'lat', 'zoom'];

    // ========================================================================= //
    // Helpers
    // ========================================================================= //

    private static function _root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function _read(string $relative): string
    {
        return file_get_contents(static::_root() . '/' . $relative);
    }

    /**
     * Get the body of a brace-delimited block, starting at the first `{` after `$anchor`.
     */
    private static function _block(string $source, string $anchor): string
    {
        $start = strpos($source, $anchor);
        if (false === $start) {
            return '';
        }

        $open = strpos($source, '{', $start);
        $depth = 0;
        $length = strlen($source);

        for ($i = $open; $i < $length; $i++) {
            if ('{' === $source[$i]) {
                $depth++;
            } elseif ('}' === $source[$i]) {
                $depth--;
                if (0 === $depth) {
                    return substr($source, $open, $i - $open + 1);
                }
            }
        }

        return '';
    }

    /**
     * Get the text between two markers, or an empty string if either is missing.
     */
    private static function _between(string $source, string $from, string $to): string
    {
        $start = strpos($source, $from);
        if (false === $start) {
            return '';
        }

        $end = strpos($source, $to, $start);
        if (false === $end) {
            return '';
        }

        return substr($source, $start, $end - $start);
    }

    /**
     * Get every `'key' =>` in a chunk of PHP.
     */
    private static function _arrayKeys(string $php): array
    {
        preg_match_all("/'(\\w+)'\\s*=>/", $php, $matches);
        return array_values(array_unique($matches[1]));
    }

    private static function _all(): array
    {
        return array_merge(static::META, static::SUBFIELDS, static::COORDS);
    }

    private function _assertSameSet(array $expected, array $actual, string $site): void
    {
        $missing = array_diff($expected, $actual);
        $extra = array_diff($actual, $expected);

        $this->assertSame([], array_values($missing), "{$site} is missing: " . implode(', ', $missing));
        $this->assertSame([], array_values($extra), "{$site} has unexpected keys: " . implode(', ', $extra));
    }

    // ========================================================================= //
    // The anchor
    // ========================================================================= //

    public function testTheSubfieldListMatchesDefaults(): void
    {
        // Every other site below is checked against these constants, so pin them to the source of truth.
        $this->assertSame(static::SUBFIELDS, array_column(Defaults::SUBFIELDCONFIG, 'handle'));
    }

    // ========================================================================= //
    // Every site
    // ========================================================================= //

    /**
     * @dataProvider siteProvider
     */
    public function testSiteCarriesExactlyItsKeys(string $site, array $actual, array $expected): void
    {
        $this->assertNotEmpty($actual, "Could not find any keys for {$site}. Has the code moved?");
        $this->_assertSameSet($expected, $actual, $site);
    }

    public static function siteProvider(): array
    {
        $field = static::_read('src/fields/AddressField.php');
        $store = static::_read('src/web/assets/src/vue/stores/AddressStore.js');

        // Install columns, minus the bookkeeping ones
        $install = static::_block(static::_read('src/migrations/Install.php'), 'private function _createTables');
        $installKeys = array_diff(
            static::_arrayKeys($install),
            ['id', 'elementId', 'fieldId', 'dateCreated', 'dateUpdated', 'uid']
        );

        // Record docblock properties, minus the bookkeeping and the query-only `distance`
        preg_match_all('/@property \S+ \$(\w+)/', static::_read('src/records/Address.php'), $record);
        $recordKeys = array_diff($record[1], ['id', 'elementId', 'fieldId', 'distance']);

        // Model properties declared on Address or Location
        $modelKeys = array_diff(
            array_map(
                static fn(ReflectionProperty $p): string => $p->getName(),
                (new ReflectionClass(Address::class))->getProperties(ReflectionProperty::IS_PUBLIC)
            ),
            ['id', 'elementId', 'fieldId', 'distance']
        );

        // Search keywords read `$value->x`
        preg_match_all('/\$value->(\w+)/', static::_block($field, 'public function getSearchKeywords'), $keywords);

        // updateData() assigns `addressData.x` and `addressCoords.x`
        $update = static::_block($store, 'function updateData(');
        preg_match_all('/addressData\.(\w+)\s*=/', $update, $storeAddress);
        preg_match_all('/addressCoords\.(\w+)\s*=/', $update, $storeCoords);

        // The data handed to Vue, minus the two group keys
        $vueData = array_diff(
            static::_arrayKeys(static::_block($field, 'private function _getAddressData')),
            ['address', 'coords']
        );

        $all = static::_all();
        $text = array_diff(static::SUBFIELDS, ['mapboxId']);

        return [
            'Install migration' => ['Install migration', array_values($installKeys), $all],
            'Record @property docblock' => ['Record @property docblock', array_values($recordKeys), $all],
            'Address model properties' => ['Address model properties', array_values($modelKeys), $all],
            'afterElementSave()' => [
                'afterElementSave()',
                static::_arrayKeys(static::_between($field, '$record->setAttributes([', '], false);')),
                $all,
            ],
            'normalizeValue() array branch' => [
                'normalizeValue() array branch',
                array_values(array_diff(
                    static::_arrayKeys(static::_block($field, 'if (is_array($value))')),
                    ['elementId', 'fieldId']
                )),
                $all,
            ],
            '_getAddressData()' => ['_getAddressData()', array_values($vueData), $all],
            'getSearchKeywords()' => [
                'getSearchKeywords()',
                array_values(array_unique($keywords[1])),
                array_merge(['formatted'], array_values($text)),
            ],
            'AddressStore.updateData() address' => [
                'AddressStore.updateData() address',
                array_values(array_unique($storeAddress[1])),
                array_merge(static::META, static::SUBFIELDS),
            ],
            'AddressStore.updateData() coords' => [
                'AddressStore.updateData() coords',
                array_values(array_unique($storeCoords[1])),
                static::COORDS,
            ],
        ];
    }

    // ========================================================================= //
    // Related checks
    // ========================================================================= //

    public function testTheHiddenMetaInputsPostFormattedAndRaw(): void
    {
        $meta = static::_read('src/web/assets/src/vue/address/address-meta.vue');

        foreach (static::META as $key) {
            $this->assertStringContainsString("[{$key}]", $meta);
        }
    }

    public function testTheCoordinateInputsPostAllThreeKeys(): void
    {
        $coords = static::_read('src/web/assets/src/vue/address/address-coords.vue');

        foreach (static::COORDS as $key) {
            $this->assertStringContainsString("key: '{$key}'", $coords);
        }
    }

    public function testRawIsDecodedOnLoadAndEncodedForVue(): void
    {
        $field = static::_read('src/fields/AddressField.php');

        $this->assertStringContainsString("Json::decode(\$attr['raw'])", $field);
        $this->assertStringContainsString('Json::encode($raw)', $field);
    }

    public function testTheBookkeepingColumnsAreLeftOutOfTheModel(): void
    {
        $this->assertStringContainsString(
            "\$omitColumns = ['dateCreated','dateUpdated','uid'];",
            static::_read('src/fields/AddressField.php')
        );
    }
}
