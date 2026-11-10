<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\helpers\ApiHelper;
use doublesecretagency\mapbox\helpers\Mapbox;
use doublesecretagency\mapbox\models\DynamicMap;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use yii\base\Exception;

/**
 * Tests for the public PHP API, `helpers/Mapbox.php`, which Twig sees as `mapbox`.
 *
 * Every method here is documented public surface. The map registry and the
 * API URLs work without Craft and are called directly; the asset loading and
 * token lookup read plugin settings, so they are pinned at the source level.
 */
class MapboxFacadeTest extends TestCase
{
    private ReflectionClass $reflection;
    private string $source;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Mapbox::class);
        $this->source = file_get_contents($this->reflection->getFileName());
    }

    // ========================================================================= //
    // Public surface
    // ========================================================================= //

    /**
     * @dataProvider publicMethodProvider
     */
    public function testPublicStaticMethodExists(string $method, int $params): void
    {
        $this->assertTrue($this->reflection->hasMethod($method), "Mapbox::{$method}() is missing.");

        $reflection = $this->reflection->getMethod($method);

        $this->assertTrue($reflection->isPublic());
        $this->assertTrue($reflection->isStatic());
        $this->assertSame($params, $reflection->getNumberOfParameters());
    }

    public static function publicMethodProvider(): array
    {
        return [
            'getAssets' => ['getAssets', 2],
            'loadAssets' => ['loadAssets', 2],
            'map' => ['map', 2],
            'getMap' => ['getMap', 1],
            'getApiUrl' => ['getApiUrl', 2],
            'getAccessToken' => ['getAccessToken', 0],
            'setAccessToken' => ['setAccessToken', 1],
        ];
    }

    public function testThereAreNoOtherPublicMethods(): void
    {
        $public = array_map(
            static fn(ReflectionMethod $m): string => $m->getName(),
            $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC)
        );
        sort($public);

        $expected = array_keys(static::publicMethodProvider());
        sort($expected);

        $this->assertSame($expected, $public);
    }

    // ========================================================================= //
    // Map registry
    // ========================================================================= //

    public function testMapReturnsADynamicMap(): void
    {
        $this->assertInstanceOf(DynamicMap::class, Mapbox::map());
    }

    public function testGetMapReturnsTheSameMapObject(): void
    {
        $map = Mapbox::map([], ['id' => 'facade-test-map']);

        $this->assertSame($map, Mapbox::getMap('facade-test-map'));
    }

    public function testGetMapForAnUnknownIdThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The map "no-such-map" does not exist.');

        Mapbox::getMap('no-such-map');
    }

    // ========================================================================= //
    // API URLs
    // ========================================================================= //

    /**
     * @dataProvider apiUrlProvider
     */
    public function testApiUrl(string $service, string $url): void
    {
        $this->assertSame($url, Mapbox::getApiUrl($service));
    }

    public static function apiUrlProvider(): array
    {
        return [
            'maps' => ['maps', 'https://api.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.js'],
            'search' => ['search', 'https://api.mapbox.com/search-js/v1.0.0-beta.17/core.js'],
            'language' => ['language', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-language/v1.0.0/mapbox-gl-language.js'],
        ];
    }

    public function testUnknownServiceHasNoUrl(): void
    {
        $this->assertNull(Mapbox::getApiUrl('navigation'));
    }

    public function testParamsAreAppendedToTheUrl(): void
    {
        // Source-level: with params, `UrlHelper::urlWithParams()` builds the query through Craft.
        $helper = file_get_contents((new ReflectionClass(ApiHelper::class))->getFileName());

        $this->assertStringContainsString('return UrlHelper::urlWithParams($apiUrl, $params);', $helper);
    }

    // ========================================================================= //
    // Delegation (source-level)
    // ========================================================================= //

    /**
     * @dataProvider delegationProvider
     */
    public function testFacadeDelegatesToTheApiHelper(string $call): void
    {
        $this->assertStringContainsString($call, $this->source);
    }

    public static function delegationProvider(): array
    {
        return [
            'getApiUrl' => ['return ApiHelper::getApiUrl($service, $params);'],
            'getAccessToken' => ['return ApiHelper::getAccessToken();'],
            'setAccessToken' => ['return ApiHelper::setAccessToken($token);'],
        ];
    }

    public function testSetAccessTokenReturnsTheToken(): void
    {
        $this->assertSame('pk.test', ApiHelper::setAccessToken('pk.test'));
    }

    public function testAssetsAreTheApiThenTheBundledScripts(): void
    {
        $this->assertMatchesRegularExpression(
            '/\$files = \[self::getApiUrl\(\$service, \$params\)\];[\s\S]*?getBundle\(DynamicMapAsset::class\)[\s\S]*?getAssetUrl\(\$bundle, \$file\)/',
            $this->source
        );
    }

    /**
     * Cloud only uploads folders a bundle declares, so the map scripts must
     * come from a bundle, never from a hand-built published URL.
     */
    public function testAssetsAreNotPublishedByHand(): void
    {
        $this->assertStringNotContainsString('getPublishedUrl(', $this->source);
    }

    public function testLoadAssetsRegistersTheApiThenTheBundleThenTheToken(): void
    {
        $api = strpos($this->source, "registerJsFile(static::getApiUrl(\$service, \$params), ['position' => View::POS_HEAD])");
        $bundle = strpos($this->source, 'registerAssetBundle(DynamicMapAsset::class)');
        $token = strpos($this->source, "registerJs(\"window.mapboxAccessToken='{\$accessToken}'\", View::POS_HEAD)");

        $this->assertNotFalse($api);
        $this->assertNotFalse($bundle);
        $this->assertNotFalse($token);
        $this->assertLessThan($bundle, $api);
        $this->assertLessThan($token, $bundle);
    }

    public function testTheTokenIsParsedForEnvironmentVariables(): void
    {
        $helper = file_get_contents((new ReflectionClass(ApiHelper::class))->getFileName());

        $this->assertStringContainsString('App::parseEnv($settings->accessToken)', $helper);
    }
}
