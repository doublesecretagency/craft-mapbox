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
