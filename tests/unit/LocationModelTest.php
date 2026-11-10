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
 * The distance math is pinned to precise values, so a change to either
 * earth-radius constant or to the formula is caught. Before 2.2.0 the radius
 * was hardcoded to zero, so every distance was 0.0 (findings #3).
 */
class LocationModelTest extends TestCase
{
    /** Los Angeles City Hall, roughly. */
    private const LA = ['lng' => -118.2437, 'lat' => 34.0522];

    /** Lower Manhattan, roughly. */
    private const NY = ['lng' => -74.0060, 'lat' => 40.7128];

    /** The real distance between them, to within a mile. */
    private const LA_TO_NY_MILES = 2445.7101588447485;
    private const LA_TO_NY_KM = 3935.746254609723;

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
    // Distance
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

    public function testDistanceDefaultsToMiles(): void
    {
        $distance = (new Location(self::LA))->getDistance(new Location(self::NY));

        $this->assertEqualsWithDelta(self::LA_TO_NY_MILES, $distance, 0.0001);
    }

    /**
     * @dataProvider unitAliasProvider
     */
    public function testDistanceUnitAlias(string $unit, float $expected): void
    {
        $distance = (new Location(self::LA))->getDistance(new Location(self::NY), $unit);

        $this->assertEqualsWithDelta($expected, $distance, 0.0001);
    }

    public static function unitAliasProvider(): array
    {
        return [
            'miles'      => ['miles', self::LA_TO_NY_MILES],
            'mi'         => ['mi', self::LA_TO_NY_MILES],
            'km'         => ['km', self::LA_TO_NY_KM],
            'kilometers' => ['kilometers', self::LA_TO_NY_KM],
        ];
    }

    public function testUnrecognizedUnitFallsBackToMiles(): void
    {
        $distance = (new Location(self::LA))->getDistance(new Location(self::NY), 'furlongs');

        $this->assertEqualsWithDelta(self::LA_TO_NY_MILES, $distance, 0.0001);
    }

    public function testDistanceToSelfIsZero(): void
    {
        $la = new Location(self::LA);

        $this->assertSame(0.0, $la->getDistance($la));
    }

    /**
     * Some latitudes round to 1.0000000000000002 inside `acos()`, which is NaN
     * without the clamp. Which ones depends on the platform's math library, so
     * the list covers both macOS (the Bermuda default) and Linux (the others).
     *
     * @dataProvider roundingLatitudeProvider
     */
    public function testDistanceToSelfIsNeverNan(float $lat): void
    {
        $point = new Location(['lng' => -64.7527469, 'lat' => $lat]);

        $this->assertSame(0.0, $point->getDistance($point));
    }

    public static function roundingLatitudeProvider(): array
    {
        return [
            'Bermuda default' => [32.3113966],
            '62.89031' => [62.89031],
            '26.38864' => [26.38864],
            '-1.61437' => [-1.61437],
        ];
    }

    public function testDistanceIsSymmetric(): void
    {
        $la = new Location(self::LA);
        $ny = new Location(self::NY);

        $this->assertEqualsWithDelta($la->getDistance($ny), $ny->getDistance($la), 0.0001);
    }

    public function testAnArrayTargetMatchesALocationTarget(): void
    {
        $la = new Location(self::LA);

        $this->assertSame($la->getDistance(new Location(self::NY)), $la->getDistance(self::NY));
    }

    public function testTheTwoUnitsAgreeOnTheConversionFactor(): void
    {
        $la = new Location(self::LA);
        $ny = new Location(self::NY);

        // 6371 / 3959, the ratio of the two earth-radius constants
        $this->assertEqualsWithDelta(1.609244, $la->getDistance($ny, 'km') / $la->getDistance($ny, 'miles'), 0.0001);
    }

    public function testDistanceIsNullWhenTheTargetHasNoCoordinates(): void
    {
        $this->assertNull((new Location(self::LA))->getDistance(new Location()));
    }

    public function testATargetAtNullIslandStillHasADistance(): void
    {
        // 0,0 is a real place, so a target that genuinely sits there is measured
        $distance = (new Location(self::LA))->getDistance(new Location(['lng' => 0.0, 'lat' => 0.0]));

        $this->assertEqualsWithDelta(7813.822689932664, $distance, 0.0001);
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
