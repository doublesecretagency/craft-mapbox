<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\models\DynamicMap;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Parity between the PHP and JavaScript halves of the dynamic map API.
 *
 * A map built in Twig or PHP records each call as DNA. `mapbox.js` replays
 * that DNA by switching on each segment's `type` and calling the method of the
 * same name on a JavaScript `DynamicMap`. So a new chainable method needs four
 * things to agree: the PHP method, the DNA type it writes, the `case` in
 * `_unpack()`, and the JS method. Any one missing fails silently: the call is
 * recorded and then never replayed.
 *
 * The `.min.js` twins are maintained by hand and served when `minifyJsFiles`
 * is on, so they are held to the same method and case lists.
 */
class UniversalMethodsParityTest extends TestCase
{
    /** Methods only JavaScript has: getters, and the final render. */
    private const JS_ONLY = ['getMarker', 'getPopup', 'getZoom', 'getCenter', 'getBounds', 'tag'];

    // ========================================================================= //
    // Helpers
    // ========================================================================= //

    private static function _js(string $file): string
    {
        return file_get_contents(dirname(__DIR__, 2) . "/src/resources/js/{$file}");
    }

    private static function _php(): string
    {
        return file_get_contents((new ReflectionClass(DynamicMap::class))->getFileName());
    }

    /**
     * Get every public chainable method declared on the PHP DynamicMap.
     */
    private static function _phpChainable(): array
    {
        $methods = array_filter(
            (new ReflectionClass(DynamicMap::class))->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $m): bool => DynamicMap::class === $m->getDeclaringClass()->getName()
        );

        $names = array_map(static fn(ReflectionMethod $m): string => $m->getName(), $methods);

        return array_values(array_diff($names, ['__construct', '__toString', 'tag', 'getDna']));
    }

    /**
     * Get every public `this.x = function` on the JS DynamicMap.
     */
    private static function _jsMethods(string $file): array
    {
        preg_match_all('/this\.([a-zA-Z]\w*)\s*=\s*function/', static::_js($file), $matches);

        return array_values(array_diff($matches[1], ['__construct']));
    }

    /**
     * Get every `case '<type>':` inside `_unpack()`.
     */
    private static function _unpackCases(string $file): array
    {
        $js = static::_js($file);
        $start = strpos($js, '_unpack:');
        if (false === $start) {
            return [];
        }

        preg_match_all('/case\s*[\'"](\w+)[\'"]\s*:/', substr($js, $start), $matches);

        return $matches[1];
    }

    // ========================================================================= //
    // Methods
    // ========================================================================= //

    public function testPhpHasTheKnownChainableMethods(): void
    {
        $expected = [
            'markers', 'style', 'zoom', 'center', 'fit', 'panToMarker',
            'changeMarker', 'hideMarker', 'showMarker', 'openPopup', 'closePopup',
        ];

        $this->assertEqualsCanonicalizing($expected, static::_phpChainable());
    }

    /**
     * @dataProvider jsFileProvider
     */
    public function testEveryPhpMethodHasAJavaScriptTwin(string $file): void
    {
        $missing = array_diff(static::_phpChainable(), static::_jsMethods($file));

        $this->assertSame([], array_values($missing), "{$file} is missing: " . implode(', ', $missing));
    }

    /**
     * @dataProvider jsFileProvider
     */
    public function testJavaScriptOnlyMethodsAreTheKnownAccessors(string $file): void
    {
        $jsOnly = array_values(array_diff(static::_jsMethods($file), static::_phpChainable()));

        $this->assertEqualsCanonicalizing(static::JS_ONLY, $jsOnly);
    }

    public static function jsFileProvider(): array
    {
        return [
            'dynamicmap.js' => ['dynamicmap.js'],
            'dynamicmap.min.js' => ['dynamicmap.min.js'],
        ];
    }

    public function testMinifiedDynamicMapHasExactlyTheSourceMethods(): void
    {
        // Private helpers included, so a renamed helper in one file fails too.
        preg_match_all('/this\.(\w+)\s*=\s*function/', static::_js('dynamicmap.js'), $source);
        preg_match_all('/this\.(\w+)\s*=\s*function/', static::_js('dynamicmap.min.js'), $min);

        $this->assertSame($source[1], $min[1]);
    }

    // ========================================================================= //
    // DNA types
    // ========================================================================= //

    public function testEveryChainableMethodWritesADnaTypeOfTheSameName(): void
    {
        preg_match_all("/'type' => '(\\w+)'/", static::_php(), $types);

        $this->assertEqualsCanonicalizing(
            array_merge(['map'], static::_phpChainable()),
            array_values(array_unique($types[1]))
        );
    }

    /**
     * @dataProvider mapboxFileProvider
     */
    public function testUnpackReplaysEveryDnaType(string $file): void
    {
        $this->assertEqualsCanonicalizing(
            array_merge(['map'], static::_phpChainable()),
            static::_unpackCases($file)
        );
    }

    public static function mapboxFileProvider(): array
    {
        return [
            'mapbox.js' => ['mapbox.js'],
            'mapbox.min.js' => ['mapbox.min.js'],
        ];
    }

    /**
     * Every `block.<key>` the JS reads must be a key the PHP writes.
     */
    public function testUnpackReadsOnlyKeysThePhpWrites(): void
    {
        $js = static::_js('mapbox.js');
        preg_match_all('/block\.(\w+)/', substr($js, strpos($js, '_unpack:')), $read);

        foreach (array_unique($read[1]) as $key) {
            if ('type' === $key) {
                continue;
            }
            $this->assertStringContainsString("'{$key}' =>", static::_php(), "PHP never writes `{$key}`.");
        }
    }

    // ========================================================================= //
    // The handoff
    // ========================================================================= //

    public function testTagWritesTheDnaIntoADataAttribute(): void
    {
        $this->assertStringContainsString("'data-dna' => Json::encode(\$this->_dna)", static::_php());
    }

    public function testJavaScriptReadsTheSameDataAttribute(): void
    {
        $this->assertStringContainsString('map.dataset.dna', static::_js('mapbox.js'));
    }

    public function testBothSidesUseTheSameContainerClass(): void
    {
        $this->assertStringContainsString("'class' => 'mb-map'", static::_php());
        $this->assertStringContainsString("getElementsByClassName('mb-map')", static::_js('mapbox.js'));
        $this->assertStringContainsString("classList.add('mb-map')", static::_js('dynamicmap.js'));
    }

    public function testTagRequiresTheChainToStartWithAMap(): void
    {
        $this->assertStringContainsString("'map' !== (\$this->_dna[0]['type'] ?? false)", static::_php());
        $this->assertStringContainsString("0 === i && 'map' !== block.type", static::_js('mapbox.js'));
    }

    public function testTagInitializesAfterThePageLoads(): void
    {
        $this->assertStringContainsString("\$mapboxInit = \"mapbox.init('{\$this->id}', {\$callback})\";", static::_php());
        $this->assertStringContainsString("addEventListener('load', function () {{\$mapboxInit}});", static::_php());
    }

    public function testPopupsAreHandedOverThroughTheSharedDataObject(): void
    {
        $this->assertStringContainsString("window._mbData.popups['{\$this->id}']", static::_php());
        $this->assertStringContainsString('window._mbData.popups[this.id]', static::_js('dynamicmap.js'));
    }

    // ========================================================================= //
    // Minified twins
    // ========================================================================= //

    /**
     * @dataProvider minifiedProvider
     */
    public function testMinifiedTwinExistsAndIsNotEmpty(string $file): void
    {
        $path = dirname(__DIR__, 2) . "/src/resources/js/{$file}";

        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path));
    }

    public static function minifiedProvider(): array
    {
        return [
            'mapbox.min.js' => ['mapbox.min.js'],
            'dynamicmap.min.js' => ['dynamicmap.min.js'],
        ];
    }
}
