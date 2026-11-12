<?php
namespace doublesecretagency\mapbox\tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * Source-level guards on how the front-end scripts create a map (#6).
 *
 * A map built in JavaScript starts as a detached div. Mapbox GL looks a
 * string container up by id, so it threw "Container not found" for every map
 * that wasn't already in the page. And `mapbox.js` used to define its global
 * inside DOMContentLoaded, so inline scripts saw `mapbox is not defined`.
 * Each check runs against the source and its `.min.js` twin.
 */
class DynamicMapScriptsTest extends TestCase
{
    private static function _js(string $file): string
    {
        return file_get_contents(dirname(__DIR__, 2) . "/src/resources/js/{$file}");
    }

    public static function dynamicMapProvider(): array
    {
        return [['dynamicmap.js'], ['dynamicmap.min.js']];
    }

    public static function mapboxProvider(): array
    {
        return [['mapbox.js'], ['mapbox.min.js']];
    }

    /** @dataProvider dynamicMapProvider */
    public function testTheMapIsCreatedInItsOwnElement(string $file): void
    {
        $js = static::_js($file);

        $this->assertMatchesRegularExpression('/container\s*=\s*this\.div\b/', $js);
        $this->assertDoesNotMatchRegularExpression('/container\s*=\s*this\.id\b/', $js);
    }

    /** @dataProvider dynamicMapProvider */
    public function testTheMapResizesWithItsElement(string $file): void
    {
        $this->assertMatchesRegularExpression('/new ResizeObserver\(.*?\.resize\(\)\)+\.observe\(this\.div\)/s', static::_js($file));
    }

    // ========================================================================= //
    // Map controls (#12)
    // ========================================================================= //

    public static function controlProvider(): array
    {
        $rows = [];
        foreach (['dynamicmap.js', 'dynamicmap.min.js'] as $file) {
            foreach ([
                'navigationControl' => 'NavigationControl',
                'fullscreenControl' => 'FullscreenControl',
                'scaleControl'      => 'ScaleControl',
                'geolocateControl'  => 'GeolocateControl',
            ] as $key => $class) {
                $rows["{$file} {$key}"] = [$file, $key, $class];
            }
        }
        return $rows;
    }

    /**
     * Each `mapOptions` switch maps to its Mapbox GL control class.
     *
     * @dataProvider controlProvider
     */
    public function testEachControlSwitchMapsToItsClass(string $file, string $key, string $class): void
    {
        $this->assertMatchesRegularExpression("/{$key}\s*:\s*['\"]{$class}['\"]/", static::_js($file));
    }

    /**
     * Controls are added only after the map exists, so a missing access token
     * (which bails before `new mapboxgl.Map`) adds nothing.
     *
     * @dataProvider dynamicMapProvider
     */
    public function testControlsAreAddedAfterTheMapIsBuilt(string $file): void
    {
        $js = static::_js($file);

        $map = strpos($js, 'new mapboxgl.Map(mapOptions)');
        $add = strpos($js, '.addControl(');

        $this->assertNotFalse($map);
        $this->assertNotFalse($add);
        $this->assertGreaterThan($map, $add);
    }

    /**
     * An absent or `false` switch is skipped; `true` means Mapbox's defaults;
     * an object's `position` goes to `addControl()` and the rest to the control.
     *
     * @dataProvider dynamicMapProvider
     */
    public function testControlSwitchValuesAreHandled(string $file): void
    {
        $js = static::_js($file);

        $this->assertMatchesRegularExpression('/if\s*\(\s*!value\s*\)\s*\{?\s*continue/', $js);
        $this->assertMatchesRegularExpression('/\{\s*position(:\s*position)?\s*,\s*\.\.\.controlOptions\s*\}\s*=\s*\(?\s*typeof value\s*===\s*["\']object["\']\s*\)?\s*\?\s*value\s*:\s*\{\s*\}/', $js);
        $this->assertMatchesRegularExpression('/addControl\(\s*new mapboxgl\[controlClass\]\(controlOptions\)\s*,\s*position\s*\)/', $js);
    }

    /**
     * Mapbox throws on an unknown position, which stops the rest of the map
     * from building. A typo warns and uses the default corner instead.
     *
     * @dataProvider dynamicMapProvider
     */
    public function testAnUnknownPositionFallsBackToTheDefaultCorner(string $file): void
    {
        $js = static::_js($file);

        $this->assertMatchesRegularExpression('/\[\s*["\']top-left["\']\s*,\s*["\']top-right["\']\s*,\s*["\']bottom-left["\']\s*,\s*["\']bottom-right["\']\s*\]\.includes\(position\)/', $js);
        $this->assertMatchesRegularExpression('/console\.warn\(`\[MB\] Invalid position/', $js);
        $this->assertMatchesRegularExpression('/position\s*=\s*undefined/', $js);
    }

    /**
     * The switches are read, never deleted. In a JavaScript-built map,
     * `mapOptions` is the caller's own object, so a delete would leave a
     * second map built from the same options without its controls.
     *
     * @dataProvider dynamicMapProvider
     */
    public function testControlSwitchesAreNotDeletedFromTheCallersOptions(string $file): void
    {
        $this->assertDoesNotMatchRegularExpression('/delete\s+mapOptions/', static::_js($file));
    }

    /** @dataProvider mapboxProvider */
    public function testTheGlobalExistsBeforeThePageFinishesLoading(string $file): void
    {
        $this->assertStringNotContainsString('DOMContentLoaded', static::_js($file));
    }

    /** @dataProvider mapboxProvider */
    public function testLoggingIsReadWhenUsed(string $file): void
    {
        // `_mbData` is printed after mapbox.js, so a value captured at load time is always false
        $this->assertMatchesRegularExpression('/get log\(\)/', static::_js($file));
    }
}
