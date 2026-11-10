<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\web\AssetBundle;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the asset bundles, the files they name, and the pinned CDN versions.
 *
 * A bundle naming a file that does not exist fails quietly: the browser gets a
 * 404 and the map or field simply doesn't appear. Every local file a bundle
 * lists is checked against the disk, both `.min` variants included.
 *
 * Mapbox GL JS is pinned in four separate places (two bundles, the facade's
 * CSS and the API helper's JS URL). They must always name the same version, or
 * the CSS and JS drift apart.
 *
 * 🛑 Not covered yet: `JsApiAsset` reading `MapboxPlugin::$plugin` unguarded,
 * which crashes Craft Cloud's build (findings #1). Its regression test lands
 * with that fix. Every bundle here is read as source, never constructed,
 * partly for that reason.
 */
class AssetBundlesTest extends TestCase
{
    private const BUNDLES = [
        'AddressFieldAsset',
        'AddressFieldSettingsAsset',
        'JsApiAsset',
        'MapboxAsset',
    ];

    // ========================================================================= //
    // Helpers
    // ========================================================================= //

    private static function _root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function _source(string $bundle): string
    {
        return file_get_contents(static::_root() . "/src/web/assets/{$bundle}.php");
    }

    /**
     * Resolve a bundle's `@doublesecretagency/mapbox/...` source path to disk.
     */
    private static function _sourcePath(string $bundle): ?string
    {
        if (!preg_match("/\\\$this->sourcePath = '@doublesecretagency\\/mapbox\\/([^']+)';/", static::_source($bundle), $match)) {
            return null;
        }

        return static::_root() . '/src/' . $match[1];
    }

    /**
     * Get every local (non-URL) file a bundle lists in `$this->js` or `$this->css`.
     */
    private static function _localFiles(string $bundle): array
    {
        $files = [];

        foreach (['js', 'css'] as $type) {
            if (!preg_match("/\\\$this->{$type} = \\[([\\s\\S]*?)\\];/", static::_source($bundle), $match)) {
                continue;
            }
            // A `{$min}` entry ends in `}js`, not `.js`
            preg_match_all('/[\'"]([^\'"]+[.}](?:js|css))[\'"]/', $match[1], $entries);

            foreach ($entries[1] as $entry) {
                if (str_starts_with($entry, 'http')) {
                    continue;
                }
                // Expand the optional `.min` switch into both files
                if (str_contains($entry, '{$min}')) {
                    $files[] = str_replace('{$min}', '', $entry);
                    $files[] = str_replace('{$min}', 'min.', $entry);
                    continue;
                }
                $files[] = $entry;
            }
        }

        return $files;
    }

    public static function bundleProvider(): array
    {
        return array_combine(static::BUNDLES, array_map(static fn(string $b): array => [$b], static::BUNDLES));
    }

    // ========================================================================= //
    // Bundles
    // ========================================================================= //

    public function testTheBundleListMatchesTheDirectory(): void
    {
        $onDisk = array_map(
            static fn(string $f): string => basename($f, '.php'),
            glob(static::_root() . '/src/web/assets/*.php')
        );
        sort($onDisk);

        $this->assertSame(static::BUNDLES, $onDisk);
    }

    /**
     * @dataProvider bundleProvider
     */
    public function testExtendsAssetBundle(string $bundle): void
    {
        $this->assertTrue(is_subclass_of("doublesecretagency\\mapbox\\web\\assets\\{$bundle}", AssetBundle::class));
    }

    /**
     * @dataProvider bundleProvider
     */
    public function testSourcePathExists(string $bundle): void
    {
        $path = static::_sourcePath($bundle);

        // MapboxAsset loads only from the CDN
        if ('MapboxAsset' === $bundle) {
            $this->assertNull($path);
            return;
        }

        $this->assertNotNull($path, "{$bundle} has no sourcePath.");
        $this->assertDirectoryExists($path);
    }

    /**
     * @dataProvider bundleProvider
     */
    public function testEveryListedFileExists(string $bundle): void
    {
        $path = static::_sourcePath($bundle);
        $files = static::_localFiles($bundle);

        if (!$path) {
            $this->assertSame([], $files, "{$bundle} lists local files but has no sourcePath.");
            return;
        }

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertFileExists("{$path}/{$file}", "{$bundle} names a missing file.");

            // Compare against the directory listing too: macOS matches `sortable.min.js`
            // to `Sortable.min.js`, and a Linux server would serve a 404
            $this->assertContains(
                basename($file),
                scandir(dirname("{$path}/{$file}")),
                "{$bundle} names `{$file}`, which only matches on a case-insensitive filesystem."
            );
        }
    }

    public function testTheFieldBundleLoadsAllThreeMapboxLibrariesBeforeTheField(): void
    {
        $this->assertMatchesRegularExpression(
            "/getApiUrl\\('maps'\\),\\s*Mapbox::getApiUrl\\('search'\\),\\s*Mapbox::getApiUrl\\('language'\\),\\s*'js\\/address\\.js',/",
            static::_source('AddressFieldAsset')
        );
    }

    public function testTheSettingsBundleBuildsOnTheFieldBundle(): void
    {
        $this->assertStringContainsString('AddressFieldAsset::class', static::_source('AddressFieldSettingsAsset'));
    }

    public function testTheMapScriptsDependOnTheMapboxLibrary(): void
    {
        $this->assertStringContainsString('MapboxAsset::class', static::_source('JsApiAsset'));
    }

    public function testTheMapboxLibraryLoadsInTheHead(): void
    {
        $this->assertStringContainsString("'position' => View::POS_HEAD", static::_source('MapboxAsset'));
    }

    // ========================================================================= //
    // Built files
    // ========================================================================= //

    /**
     * @dataProvider builtFileProvider
     */
    public function testBuiltFileIsCommitted(string $file): void
    {
        $this->assertFileExists(static::_root() . "/src/web/assets/dist/{$file}");
    }

    public static function builtFileProvider(): array
    {
        $files = [
            'js/address.js',
            'js/address-settings.js',
            'js/Sortable.min.js',
            'css/address.css',
            'images/maki/marker-stroked.svg',
        ];
        return array_combine($files, array_map(static fn(string $f): array => [$f], $files));
    }

    public function testTheFullMakiSetIsCopied(): void
    {
        // 2.1.0 ships every icon, not a curated subset.
        $this->assertGreaterThan(200, count(glob(static::_root() . '/src/web/assets/dist/images/maki/*.svg')));
    }

    // ========================================================================= //
    // Pinned versions
    // ========================================================================= //

    /**
     * Get every Mapbox GL JS version string in `src/`, skipping built and vendored files.
     */
    private static function _glVersions(): array
    {
        $versions = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(static::_root() . '/src'));

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (!preg_match('/\.(php|js|vue|twig)$/', $path) || preg_match('#/(dist|node_modules)/#', $path)) {
                continue;
            }
            preg_match_all('#mapbox-gl-js/(v\d+\.\d+\.\d+)/#', file_get_contents($path), $matches);
            foreach ($matches[1] as $version) {
                $versions[] = [str_replace(static::_root() . '/', '', $path), $version];
            }
        }

        return $versions;
    }

    public function testMapboxGlIsPinnedInFourPlaces(): void
    {
        $this->assertCount(4, static::_glVersions(), 'A pin was added or removed. Update this count deliberately.');
    }

    public function testEveryMapboxGlPinNamesTheSameVersion(): void
    {
        $versions = array_unique(array_column(static::_glVersions(), 1));

        $this->assertCount(1, $versions, 'Mapbox GL JS versions disagree: ' . json_encode(static::_glVersions()));
    }
}
