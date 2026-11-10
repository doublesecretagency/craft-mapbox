<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\helpers\MapHelper;
use doublesecretagency\mapbox\models\Address;
use doublesecretagency\mapbox\models\Location;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit tests for MapHelper.
 *
 * `extractCoords()` turns every accepted kind of location into the list of
 * `{lng, lat, id}` sets that becomes marker DNA. The id it derives is the
 * marker id that `panToMarker()`, `openPopup()` and friends target, so its
 * format is part of the public contract.
 *
 * Element inputs, and Addresses inside a list, look their field up through
 * Craft, so those branches are not exercised here.
 */
class MapHelperTest extends TestCase
{
    private const A = ['lng' => -118.2437, 'lat' => 34.0522];
    private const B = ['lng' => -74.006, 'lat' => 40.7128];

    // ========================================================================= //
    // generateId
    // ========================================================================= //

    public function testGenerateIdIsSixLowercaseLetters(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z]{6}$/', MapHelper::generateId());
    }

    public function testGenerateIdPrependsAPrefix(): void
    {
        $this->assertMatchesRegularExpression('/^marker-[a-z]{6}$/', MapHelper::generateId('marker'));
    }

    // ========================================================================= //
    // Empty input
    // ========================================================================= //

    public function testNullYieldsNothing(): void
    {
        $this->assertSame([], MapHelper::extractCoords(null));
    }

    public function testAnEmptyArrayYieldsNothing(): void
    {
        $this->assertSame([], MapHelper::extractCoords([]));
    }

    // ========================================================================= //
    // Single items
    // ========================================================================= //

    public function testACoordinateArrayGetsAnIdFromItsValues(): void
    {
        $this->assertSame(
            [['lng' => -118.2437, 'lat' => 34.0522, 'id' => '-118.2437,34.0522']],
            MapHelper::extractCoords(self::A)
        );
    }

    public function testACoordinateArrayKeepsAnExistingId(): void
    {
        $result = MapHelper::extractCoords(self::A + ['id' => 'home']);

        $this->assertSame('home', $result[0]['id']);
    }

    public function testALocationGetsAnIdFromItsCoordinates(): void
    {
        $this->assertSame(
            [['lng' => -118.2437, 'lat' => 34.0522, 'id' => '-118.2437,34.0522']],
            MapHelper::extractCoords(new Location(self::A))
        );
    }

    public function testAnAddressWithoutAFieldGetsAGeneratedMarkerId(): void
    {
        $result = MapHelper::extractCoords(new Address(self::A));

        $this->assertCount(1, $result);
        $this->assertSame(self::A['lng'], $result[0]['lng']);
        $this->assertSame(self::A['lat'], $result[0]['lat']);
        $this->assertMatchesRegularExpression('/^marker-[a-z]{6}$/', $result[0]['id']);
    }

    public function testASingleItemIsStillWrappedInAList(): void
    {
        // Elements can hold several Address fields, so the result is always a list.
        $this->assertTrue(array_is_list(MapHelper::extractCoords(self::A)));
    }

    // ========================================================================= //
    // Lists
    // ========================================================================= //

    public function testAListOfCoordinateArrays(): void
    {
        $this->assertSame(
            ['-118.2437,34.0522', '-74.006,40.7128'],
            array_column(MapHelper::extractCoords([self::A, self::B]), 'id')
        );
    }

    public function testAListMixingArraysAndLocations(): void
    {
        $result = MapHelper::extractCoords([self::A, new Location(self::B)]);

        $this->assertCount(2, $result);
        $this->assertSame(self::B['lat'], $result[1]['lat']);
    }

    public function testACollectionIsTreatedLikeAList(): void
    {
        $this->assertSame(
            MapHelper::extractCoords([self::A, self::B]),
            MapHelper::extractCoords(new Collection([self::A, self::B]))
        );
    }

    public function testIncompleteItemsInAListAreSkipped(): void
    {
        $result = MapHelper::extractCoords([self::A, ['lat' => 1.0], 'nonsense', null]);

        $this->assertCount(1, $result);
    }
}
