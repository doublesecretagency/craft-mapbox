<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\models\Address;
use doublesecretagency\mapbox\models\Location;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pure-unit tests for the Address model.
 *
 * Address adds the subfields and the formatting helpers to Location. The
 * formatting is plain string work, so it is tested by calling it. The two
 * lookups (`getElement()`, `getField()`) need Craft once they have an id, so
 * only their empty paths are covered.
 */
class AddressModelTest extends TestCase
{
    /** A complete US address. */
    private const FULL = [
        'street1' => '1 Main St',
        'street2' => 'Suite 2',
        'city' => 'Testville',
        'state' => 'CA',
        'zip' => '90210',
        'country' => 'United States',
    ];

    // ========================================================================= //
    // Inheritance
    // ========================================================================= //

    public function testExtendsLocation(): void
    {
        $this->assertInstanceOf(Location::class, new Address());
    }

    public function testCarriesCoordinatesLikeALocation(): void
    {
        $address = new Address(['lng' => -118.2437, 'lat' => 34.0522]);

        $this->assertTrue($address->hasCoords());
        $this->assertSame(['lng' => -118.2437, 'lat' => 34.0522], $address->getCoords());
    }

    // ========================================================================= //
    // __toString
    // ========================================================================= //

    public function testToStringPrefersTheMapboxFormattedAddress(): void
    {
        $address = new Address(self::FULL + ['formatted' => '  1 Main St, Testville  ']);

        $this->assertSame('1 Main St, Testville', (string) $address);
    }

    public function testToStringFallsBackToASingleLineMultiline(): void
    {
        // Source-level: the fallback goes through `multiline()`, which needs Craft (see below).
        $this->assertMatchesRegularExpression(
            '/public function __toString\(\)[\s\S]{0,600}\$this->multiline\(1\)/',
            $this->_source()
        );
    }

    // ========================================================================= //
    // multiline (source-level)
    // ========================================================================= //

    /**
     * `multiline()` wraps its result in `Template::raw()`, which reads the
     * app charset through `Craft::$app`. So its output cannot be produced
     * here, and the glue rules are pinned from the source instead.
     */
    private function _multiline(): string
    {
        $source = $this->_source();
        $start = strpos($source, 'public function multiline(');
        $this->assertNotFalse($start, 'multiline() should exist on Address.');
        $end = strpos($source, 'public function getDistance(', $start);
        $this->assertNotFalse($end);

        return substr($source, $start, $end - $start);
    }

    private function _source(): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/src/models/Address.php');
    }

    public function testMultilineDefaultsToThreeLines(): void
    {
        $this->assertStringContainsString('public function multiline(int $maxLines = 3): Markup', $this->_multiline());
    }

    public function testCityMovesToItsOwnLineFromTwoLines(): void
    {
        $this->assertStringContainsString("\$cityGlue = (2 <= \$maxLines ? '<br />' : ', ');", $this->_multiline());
    }

    public function testUnitMovesToItsOwnLineFromThreeLines(): void
    {
        $this->assertStringContainsString("\$unitGlue = (3 <= \$maxLines ? '<br />' : ', ');", $this->_multiline());
    }

    public function testCountryAppearsOnlyAtFourLines(): void
    {
        $this->assertMatchesRegularExpression(
            '/if \(4 <= \$maxLines\) \{\s*\$formatted \.= \(\$this->country/',
            $this->_multiline()
        );
    }

    public function testRepeatedLeadingAndTrailingCommasAreCleanedUp(): void
    {
        $body = $this->_multiline();

        $this->assertStringContainsString("preg_replace('/(, ){2,}/', ', ', \$formatted)", $body);
        $this->assertStringContainsString("preg_replace('/^, /', '', \$formatted)", $body);
        $this->assertStringContainsString("preg_replace('/, \$/', '', \$formatted)", $body);
    }

    public function testMultilineReturnsTrimmedRawMarkup(): void
    {
        $this->assertStringContainsString('return Template::raw(trim($formatted));', $this->_multiline());
    }

    // ========================================================================= //
    // isEmpty
    // ========================================================================= //

    public function testANewAddressIsEmpty(): void
    {
        $this->assertTrue((new Address())->isEmpty());
    }

    /**
     * @dataProvider countedSubfieldProvider
     */
    public function testEachPostalSubfieldMakesItNonEmpty(string $handle): void
    {
        $this->assertFalse((new Address([$handle => 'x']))->isEmpty());
    }

    public static function countedSubfieldProvider(): array
    {
        $handles = ['street1', 'street2', 'city', 'state', 'zip', 'country'];
        return array_combine($handles, array_map(static fn(string $h): array => [$h], $handles));
    }

    /**
     * Pinned because it surprises: an Address holding only a place name,
     * neighborhood, county, Mapbox ID or coordinates still counts as empty.
     *
     * @dataProvider ignoredSubfieldProvider
     */
    public function testNonPostalSubfieldsDoNotCount(string $handle): void
    {
        $this->assertTrue((new Address([$handle => 'x']))->isEmpty());
    }

    public static function ignoredSubfieldProvider(): array
    {
        $handles = ['name', 'neighborhood', 'county', 'mapboxId', 'formatted'];
        return array_combine($handles, array_map(static fn(string $h): array => [$h], $handles));
    }

    public function testCoordinatesAloneDoNotCount(): void
    {
        $this->assertTrue((new Address(['lng' => 1.0, 'lat' => 2.0]))->isEmpty());
    }

    // ========================================================================= //
    // Distance and lookups
    // ========================================================================= //

    public function testGetDistanceReturnsAStoredDistanceWhenNoTargetIsGiven(): void
    {
        // A proximity search sets `distance`; asking without a target returns it.
        $this->assertSame(12.5, (new Address(['distance' => 12.5]))->getDistance());
    }

    public function testGetDistanceWithNoStoredDistanceAndNoTargetIsNull(): void
    {
        $this->assertNull((new Address(['lng' => 1.0, 'lat' => 2.0]))->getDistance());
    }

    public function testGetDistanceWithATargetComputesTheRealDistance(): void
    {
        // A stored distance only answers when no target is given
        $address = new Address(['lng' => -118.2437, 'lat' => 34.0522, 'distance' => 12.5]);
        $distance = $address->getDistance(['lng' => -74.0060, 'lat' => 40.7128]);

        $this->assertEqualsWithDelta(2445.7101588447485, $distance, 0.0001);
    }

    public function testGetElementWithoutAnElementIdIsNull(): void
    {
        $this->assertNull((new Address())->getElement());
    }

    /**
     * On a multisite project the Address belongs to one site, so its element
     * must be loaded on that same site (source-level: the lookup needs Craft).
     */
    public function testGetElementLoadsTheElementOnTheAddressesSite(): void
    {
        $source = file_get_contents((new ReflectionClass(Address::class))->getFileName());
        $this->assertStringContainsString('getElementById($this->elementId, null, $this->siteId)', $source);
    }

    public function testGetFieldWithoutAFieldIdIsNull(): void
    {
        $this->assertNull((new Address())->getField());
    }
}
