<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\models\DynamicMap;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit tests for the DNA a DynamicMap records.
 *
 * Every chained call on a PHP map appends one segment to `_dna`. `tag()` writes
 * that array into the container's `data-dna` attribute, and `mapbox.js`
 * replays it segment by segment. So the DNA is the whole contract between PHP
 * and JavaScript, and building it needs no Craft container as long as the
 * locations are plain coordinates and no popup template is involved.
 *
 * `tag()` itself registers assets through the view, so it is covered at the
 * source level in UniversalMethodsParityTest.
 */
class DynamicMapDnaTest extends TestCase
{
    private const A = ['lng' => -118.2437, 'lat' => 34.0522];

    private function _map(array $locations = [], array $options = []): DynamicMap
    {
        return new DynamicMap($locations, $options);
    }

    // ========================================================================= //
    // Construction
    // ========================================================================= //

    public function testTheFirstSegmentIsAlwaysTheMap(): void
    {
        $dna = $this->_map()->getDna();

        $this->assertSame('map', $dna[0]['type']);
        $this->assertSame([], $dna[0]['locations']);
    }

    public function testAMapWithoutLocationsHasOnlyTheMapSegment(): void
    {
        $this->assertCount(1, $this->_map()->getDna());
    }

    public function testAnIdIsGeneratedWhenNoneIsGiven(): void
    {
        $map = $this->_map();

        $this->assertMatchesRegularExpression('/^map-[a-z]{6}$/', $map->id);
        $this->assertSame($map->id, $map->getDna()[0]['options']['id']);
    }

    public function testAGivenIdIsUsed(): void
    {
        $this->assertSame('my-map', $this->_map([], ['id' => 'my-map'])->id);
    }

    public function testInitialLocationsBecomeAMarkersSegment(): void
    {
        $dna = $this->_map(self::A)->getDna();

        $this->assertCount(2, $dna);
        $this->assertSame('markers', $dna[1]['type']);
        $this->assertSame('-118.2437,34.0522', $dna[1]['locations'][0]['id']);
    }

    public function testTheMapIdDoesNotLeakIntoMarkerOptions(): void
    {
        // Otherwise every marker would share the map's id.
        $dna = $this->_map(self::A, ['id' => 'my-map', 'zoom' => 4])->getDna();

        $this->assertSame(['id' => 'my-map', 'zoom' => 4], $dna[0]['options']);
        $this->assertSame(['zoom' => 4], $dna[1]['options']);
    }

    public function testNonArrayOptionsAreIgnored(): void
    {
        $dna = $this->_map([], [])->getDna();

        $this->assertSame(['id'], array_keys($dna[0]['options']));
    }

    public function testToStringPointsAtTag(): void
    {
        $this->assertStringContainsString('.tag()', (string) $this->_map());
    }

    // ========================================================================= //
    // Chainable methods
    // ========================================================================= //

    /**
     * @dataProvider chainProvider
     */
    public function testEachMethodAppendsExactlyOneSegment(string $method, array $args, array $segment): void
    {
        $map = $this->_map();
        $result = $map->{$method}(...$args);

        $this->assertSame($map, $result, "{$method}() must return the map for chaining.");
        $this->assertCount(2, $map->getDna());
        $this->assertSame($segment, $map->getDna()[1]);
    }

    public static function chainProvider(): array
    {
        $coords = ['lng' => 1.0, 'lat' => 2.0];

        return [
            'markers' => ['markers', [$coords, ['id' => 'x']], [
                'type' => 'markers',
                'locations' => [['lng' => 1.0, 'lat' => 2.0, 'id' => '1,2']],
                'options' => ['id' => 'x'],
            ]],
            'style' => ['style', ['outdoors-v12'], ['type' => 'style', 'mapStyle' => 'outdoors-v12']],
            'style (array)' => ['style', [['version' => 8]], ['type' => 'style', 'mapStyle' => ['version' => 8]]],
            'zoom' => ['zoom', [7], ['type' => 'zoom', 'level' => 7]],
            'center' => ['center', [$coords], ['type' => 'center', 'coords' => $coords]],
            'fit' => ['fit', [['padding' => 10]], ['type' => 'fit', 'options' => ['padding' => 10]]],
            'fit (null)' => ['fit', [null], ['type' => 'fit', 'options' => null]],
            'panToMarker' => ['panToMarker', ['m1'], ['type' => 'panToMarker', 'markerId' => 'm1']],
            'changeMarker' => ['changeMarker', ['m1', ['color' => 'red']], [
                'type' => 'changeMarker',
                'markerId' => 'm1',
                'options' => ['color' => 'red'],
            ]],
            'hideMarker' => ['hideMarker', ['*'], ['type' => 'hideMarker', 'markerId' => '*']],
            'showMarker' => ['showMarker', [['m1', 'm2']], ['type' => 'showMarker', 'markerId' => ['m1', 'm2']]],
            'openPopup' => ['openPopup', ['m1'], ['type' => 'openPopup', 'markerId' => 'm1']],
            'closePopup' => ['closePopup', ['m1'], ['type' => 'closePopup', 'markerId' => 'm1']],
        ];
    }

    public function testCenteringOnNothingAddsNothing(): void
    {
        $map = $this->_map();
        $map->center([]);

        $this->assertCount(1, $map->getDna());
    }

    public function testAddingNoMarkersAddsNothing(): void
    {
        $map = $this->_map();
        $map->markers(null);
        $map->markers([]);

        $this->assertCount(1, $map->getDna());
    }

    public function testSegmentsKeepCallOrder(): void
    {
        $map = $this->_map()->zoom(3)->style('dark-v11')->zoom(5);

        $this->assertSame(['map', 'zoom', 'style', 'zoom'], array_column($map->getDna(), 'type'));
    }
}
