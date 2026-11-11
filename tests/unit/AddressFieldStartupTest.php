<?php
namespace doublesecretagency\mapbox\tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * Source-level guards on how the Address field starts up (#15, PR #17).
 *
 * The field loads Mapbox GL and the search library from Mapbox's CDN. Before
 * Craft 5.10, Craft didn't wait for those scripts when it added a field to the
 * page later (a slideout, a new Matrix block), so the field could start before
 * they existed and die on `SearchBoxCore`. Separately, the map is created
 * after a short delay (or after geolocation), so an address picked before then
 * hit `setLngLat` / `setZoom` on null and left the map in the wrong place.
 *
 * Every field on a page calls `initAddressField()` as it loads, so the wait
 * must keep a single retry pending. One retry chain per field shared a single
 * attempt counter and gave up early (three fields hit the limit at ~3s, not 10s).
 */
class AddressFieldStartupTest extends TestCase
{
    private static function _src(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2) . "/src/web/assets/src/{$path}");
    }

    private static function _dist(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2) . "/src/web/assets/dist/{$path}");
    }

    private static function _method(string $code, string $name): string
    {
        // Match the definition (`name(args)` then `{` on the next line), not a `this.name()` call
        if (!preg_match('/^ {8}' . preg_quote($name, '/') . '\([^)]*\)\s*\{/m', $code, $m, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $start = $m[0][1];
        $end = strpos($code, "\n        },", $start);

        return (false === $end) ? '' : substr($code, $start, $end - $start);
    }

    public function testFieldsWaitForBothMapboxLibraries(): void
    {
        $js = static::_src('js/address.js');

        $this->assertStringContainsString('!window.mapboxgl || !window.mapboxsearchcore', $js);
        $this->assertMatchesRegularExpression('/setTimeout\(\(\) => \{[^}]*window\.initAddressField\(\);/s', $js);
    }

    public function testTheWaitGivesUpEventually(): void
    {
        // A blocked CDN (ad blocker, CSP, offline) must not poll forever
        $js = static::_src('js/address.js');

        $this->assertMatchesRegularExpression('/librariesAttempts\+\+\s*>=\s*\d+/', $js);
        $this->assertStringContainsString("console.warn('[MB] Unable to load the Mapbox libraries.')", $js);
    }

    public function testOnlyOneRetryIsPendingAtATime(): void
    {
        $js = static::_src('js/address.js');

        $this->assertMatchesRegularExpression('/if \(retryPending\)\s*\{\s*return;/', $js);
        $this->assertStringContainsString('retryPending = true;', $js);
        $this->assertStringContainsString('retryPending = false;', $js);

        // The flag is checked before the counter moves, or every extra field still burns an attempt
        $this->assertLessThan(strpos($js, 'librariesAttempts++'), strpos($js, 'if (retryPending)'));
    }

    public function testTheWaitHappensBeforeAnyFieldMounts(): void
    {
        $js = static::_src('js/address.js');

        $wait = strpos($js, '!window.mapboxgl');
        $mount = strpos($js, 'createApp(AddressField');

        $this->assertNotFalse($wait);
        $this->assertLessThan($mount, $wait);
    }

    public function testARunningFieldIsNotMountedAgain(): void
    {
        // Every field load re-runs initAddressField() over every config on the page
        $js = static::_src('js/address.js');

        $this->assertMatchesRegularExpression('/if \(element\.dataset\.mbMounted\)\s*\{\s*continue;/', $js);
        $this->assertStringContainsString("element.dataset.mbMounted = 'true';", $js);
        $this->assertStringNotContainsString('__vue_app__', $js);
    }

    public function testMapWatchersWaitForTheMap(): void
    {
        $vue = static::_src('vue/address/address-map.vue');

        $this->assertMatchesRegularExpression('/if \(!this\.marker\)\s*\{\s*return;/', static::_method($vue, '_updateMarkerPosition'));
        $this->assertMatchesRegularExpression('/if \(!this\.map\)\s*\{\s*return;/', static::_method($vue, '_updateZoomLevel'));
    }

    public function testTheMapStartsAtAnAddressPickedWhileItLoaded(): void
    {
        $this->assertStringContainsString(
            'addressStore.validateCoords(addressStore.data.coords)',
            static::_method(static::_src('vue/address/address-map.vue'), 'initMap')
        );
    }

    public function testTheCompiledFieldIncludesTheFix(): void
    {
        // The plugin serves dist/, so a source change without a rebuild ships nothing
        $dist = static::_dist('js/address.js');

        $this->assertStringContainsString('retryPending', $dist);
        $this->assertStringContainsString('mbMounted', $dist);
        $this->assertStringContainsString('If an address was chosen while the map was loading', $dist);
    }
}
