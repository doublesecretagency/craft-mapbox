<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\models\Location;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pure-unit tests for the Location value object.
 *
 * Location is the base of Address and holds a coordinate pair. None of it
 * needs a Craft container, so these are real behavior tests.
 *
 * 🛑 The distance VALUE is deliberately not asserted here. As of v2.1.0 the
 * earth radius is hardcoded to zero (findings #3), so `getDistance()` returns
 * 0.0 for any two real points. Asserting 0.0 would lock the bug in, and
 * asserting the right answer would start the suite red. The value tests land
 * with the fix. Only the null paths, which work today, are covered.
 */
class LocationModelTest extends TestCase
{
    /** Los Angeles City Hall, roughly. */
    private const LA = ['lng' => -118.2437, 'lat' => 34.0522];

    // ========================================================================= //
    // Coordinates
    // ========================================================================= //

    public function testNewLocationHasNoCoordinates(): void
    {
        $location = new Location();

        $this->assertNull($location->lng);
        $this->assertNull($location->lat);
    }

    public function testHasCoordsIsFalseWithoutBoth(): void
    {
        $this->assertFalse((new Location())->hasCoords());
        $this->assertFalse((new Location(['lat' => 34.0522]))->hasCoords());
        $this->assertFalse((new Location(['lng' => -118.2437]))->hasCoords());
    }

    public function testHasCoordsIsTrueWithBoth(): void
    {
        $this->assertTrue((new Location(self::LA))->hasCoords());
    }

    public function testZeroZeroIsAValidCoordinatePair(): void
    {
        // Null Island is a real place, and `is_numeric(0.0)` is true.
        $this->assertTrue((new Location(['lng' => 0.0, 'lat' => 0.0]))->hasCoords());
    }

    public function testGetCoordsReturnsLngThenLat(): void
    {
        // Mapbox orders longitude first.
        $this->assertSame(
            ['lng' => -118.2437, 'lat' => 34.0522],
            (new Location(self::LA))->getCoords()
        );
    }

    public function testGetCoordsReturnsNullsWhenEmpty(): void
    {
        $this->assertSame(['lng' => null, 'lat' => null], (new Location())->getCoords());
    }

    public function testToStringIsLngCommaLat(): void
    {
        $this->assertSame('-118.2437, 34.0522', (string) new Location(self::LA));
    }

    // ========================================================================= //
    // Distance (null paths only, see class docblock)
    // ========================================================================= //

    public function testGetDistanceWithoutATargetIsNull(): void
    {
        $this->assertNull((new Location(self::LA))->getDistance());
    }

    public function testGetDistanceFromALocationWithoutCoordsIsNull(): void
    {
        $this->assertNull((new Location())->getDistance(self::LA));
    }

    public function testGetDistanceToAnIncompleteArrayIsNull(): void
    {
        $this->assertNull((new Location(self::LA))->getDistance(['lat' => 1.0]));
    }

    public function testGetDistanceDefaultsToMiles(): void
    {
        $param = (new ReflectionMethod(Location::class, 'getDistance'))->getParameters()[1];

        $this->assertSame('units', $param->getName());
        $this->assertSame('miles', $param->getDefaultValue());
    }

    public function testGetDistanceAcceptsAnArrayOrALocation(): void
    {
        $type = (string) (new ReflectionMethod(Location::class, 'getDistance'))
            ->getParameters()[0]->getType();

        foreach (['array', 'Location', 'null'] as $part) {
            $this->assertStringContainsString($part, $type);
        }
    }
}
