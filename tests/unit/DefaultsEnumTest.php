<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\enums\Defaults;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit tests for the Defaults enum.
 *
 * `SUBFIELDCONFIG` is the canonical list and order of Address subfields. A new
 * field gets it verbatim, and the normalizer appends any of its entries an
 * older field is missing, so a change here reaches every Address field.
 */
class DefaultsEnumTest extends TestCase
{
    private const HANDLES = [
        'name',
        'street1',
        'street2',
        'city',
        'state',
        'zip',
        'neighborhood',
        'county',
        'country',
        'mapboxId',
    ];

    // ========================================================================= //
    // Coordinates
    // ========================================================================= //

    public function testDefaultCoordinatesAreTheBermudaTriangle(): void
    {
        $this->assertSame(
            ['lat' => 32.3113966, 'lng' => -64.7527469, 'zoom' => 6],
            Defaults::COORDINATES
        );
    }

    // ========================================================================= //
    // Subfields
    // ========================================================================= //

    public function testSubfieldsAreListedInDisplayOrder(): void
    {
        $this->assertSame(self::HANDLES, array_column(Defaults::SUBFIELDCONFIG, 'handle'));
    }

    public function testSubfieldConfigIsASequentialList(): void
    {
        // The normalizer tells old and new styles apart by key 0.
        $this->assertTrue(array_is_list(Defaults::SUBFIELDCONFIG));
    }

    /**
     * @dataProvider subfieldProvider
     */
    public function testEverySubfieldHasExactlyTheSixKeys(array $subfield): void
    {
        $this->assertSame(
            ['handle', 'label', 'width', 'enabled', 'autocomplete', 'required'],
            array_keys($subfield)
        );
    }

    /**
     * @dataProvider subfieldProvider
     */
    public function testEverySubfieldIsAlreadyTypecast(array $subfield): void
    {
        $this->assertIsString($subfield['handle']);
        $this->assertIsString($subfield['label']);
        $this->assertIsInt($subfield['width']);
        $this->assertIsBool($subfield['enabled']);
        $this->assertIsBool($subfield['autocomplete']);
        $this->assertIsBool($subfield['required']);
    }

    /**
     * @dataProvider subfieldProvider
     */
    public function testNoSubfieldIsRequiredByDefault(array $subfield): void
    {
        $this->assertFalse($subfield['required']);
    }

    /**
     * @dataProvider subfieldProvider
     */
    public function testWidthsArePercentages(array $subfield): void
    {
        $this->assertGreaterThan(0, $subfield['width']);
        $this->assertLessThanOrEqual(100, $subfield['width']);
    }

    public static function subfieldProvider(): array
    {
        $cases = [];
        foreach (Defaults::SUBFIELDCONFIG as $subfield) {
            $cases[$subfield['handle']] = [$subfield];
        }
        return $cases;
    }

    public function testOnlyStreetAddressAutocompletes(): void
    {
        $autocomplete = array_column(
            array_filter(Defaults::SUBFIELDCONFIG, static fn(array $s): bool => $s['autocomplete']),
            'handle'
        );

        $this->assertSame(['street1'], array_values($autocomplete));
    }

    public function testTheUsualPostalSubfieldsAreEnabled(): void
    {
        $enabled = array_column(
            array_filter(Defaults::SUBFIELDCONFIG, static fn(array $s): bool => $s['enabled']),
            'handle'
        );

        $this->assertSame(['street1', 'street2', 'city', 'state', 'zip', 'country'], array_values($enabled));
    }

    public function testCityStateAndZipShareOneRow(): void
    {
        $widths = array_column(Defaults::SUBFIELDCONFIG, 'width', 'handle');

        $this->assertSame(100, $widths['city'] + $widths['state'] + $widths['zip']);
    }
}
