<?php
namespace doublesecretagency\mapbox\tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * How a Mapbox search result is mapped into the Address subfields.
 *
 * The mapping lives in the Pinia store and only runs in the browser, against a
 * live Search Box response, so these are source-level guards on the three
 * decisions that #14 turned on. Response shapes observed 2026-11-10:
 *
 * - An `address` result repeats its street line in `properties.name`, so
 *   copying `name` blindly put the street in the name subfield twice.
 * - A `poi` result's `context.address.name` is often the WHOLE formatted
 *   address ("20 West 34th Street, Manhattan, New York, NY 10001, United
 *   States"), while `properties.address` is the street line alone.
 * - A `street` result has no `context.address` at all, so street1 was empty.
 */
class AutocompleteMappingTest extends TestCase
{
    private static function _updateData(): string
    {
        $store = file_get_contents(dirname(__DIR__, 2) . '/src/web/assets/src/vue/stores/AddressStore.js');

        $start = strpos($store, 'function updateData(');
        $end = strpos($store, '// Reset suggestions', (int) $start);

        return (false === $start || false === $end) ? '' : substr($store, $start, $end - $start);
    }

    public function testUpdateDataCanBeFound(): void
    {
        $this->assertNotSame('', static::_updateData(), 'Could not find updateData() in AddressStore.js. Has it moved?');
    }

    public function testOnlyAPointOfInterestGetsAName(): void
    {
        $this->assertStringContainsString("'poi' === type", static::_updateData());
    }

    public function testANameRepeatingTheStreetIsDropped(): void
    {
        $this->assertStringContainsString('p.name === addressData.street1', static::_updateData());
    }

    public function testTheNameIsDecidedAfterTheStreet(): void
    {
        $code = static::_updateData();

        // The repeat check reads street1, so street1 must already be set
        $street = strpos($code, 'addressData.street1 ');
        $name = strpos($code, 'addressData.name ');

        $this->assertNotFalse($street);
        $this->assertNotFalse($name);
        $this->assertLessThan($name, $street, 'addressData.name is set before addressData.street1, so the repeat check reads a stale value.');
    }

    public function testTheNameIsNeverCopiedUnconditionally(): void
    {
        $this->assertDoesNotMatchRegularExpression('/addressData\.name\s*=\s*\(feature\.properties\.name \|\| null\);/', static::_updateData());
    }

    public function testStreetOnePrefersTheSingleLineStreetAddress(): void
    {
        $code = static::_updateData();

        // `properties.address` must be tried before `context.address.name`, which can hold the whole formatted address
        $this->assertMatchesRegularExpression('/p\.address\s*\|\|\s*\(c\.address \? c\.address\.name : null\)/', $code);
    }

    public function testAStreetResultFillsStreetOne(): void
    {
        $this->assertStringContainsString("'street' === type", static::_updateData());
    }
}
