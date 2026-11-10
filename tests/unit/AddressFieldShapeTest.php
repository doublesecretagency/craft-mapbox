<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use doublesecretagency\mapbox\enums\Defaults;
use doublesecretagency\mapbox\fields\AddressField;
use doublesecretagency\mapbox\models\Address;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Structural tests for the Address field.
 *
 * Nearly every method on the field reaches for the view, the request or the
 * database, so the field is pinned by its shape: the settings it exposes and
 * their defaults (read through `getDefaultProperties()`, never `new`), and the
 * signatures Craft calls.
 */
class AddressFieldShapeTest extends TestCase
{
    private ReflectionClass $reflection;
    private string $source;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(AddressField::class);
        $this->source = file_get_contents($this->reflection->getFileName());
    }

    // ========================================================================= //
    // Type
    // ========================================================================= //

    public function testExtendsField(): void
    {
        $this->assertTrue($this->reflection->isSubclassOf(Field::class));
    }

    public function testCanBeAnIndexColumn(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(PreviewableFieldInterface::class));
    }

    public function testStoresNothingInTheContentColumn(): void
    {
        // Data lives only in `mapbox_addresses`.
        $this->assertFalse(AddressField::hasContentColumn());
    }

    public function testDisplayNameIsAddressMapbox(): void
    {
        $this->assertStringContainsString("Craft::t('mapbox', 'Address (Mapbox)')", $this->source);
    }

    // ========================================================================= //
    // Settings and defaults
    // ========================================================================= //

    /**
     * @dataProvider settingProvider
     */
    public function testSettingDefault(string $property, mixed $default): void
    {
        $defaults = $this->reflection->getDefaultProperties();

        $this->assertArrayHasKey($property, $defaults);
        $this->assertSame($default, $defaults[$property]);
    }

    public static function settingProvider(): array
    {
        return [
            'showMap' => ['showMap', false],
            'mapOnStart' => ['mapOnStart', 'default'],
            'mapOnSearch' => ['mapOnSearch', 'open'],
            'visibilityToggle' => ['visibilityToggle', 'both'],
            'coordinatesMode' => ['coordinatesMode', 'readOnly'],
            'requireCoordinates' => ['requireCoordinates', true],
            'coordinatesDefault' => ['coordinatesDefault', Defaults::COORDINATES],
            'subfieldConfig' => ['subfieldConfig', Defaults::SUBFIELDCONFIG],
        ];
    }

    /**
     * @dataProvider settingProvider
     */
    public function testSettingIsPublic(string $property): void
    {
        // Craft saves only public properties as field settings.
        $this->assertTrue($this->reflection->getProperty($property)->isPublic());
    }

    /**
     * Every option the settings screen offers must be a value the field
     * understands, and vice versa.
     *
     * @dataProvider optionProvider
     */
    public function testSettingsScreenOffersEachDocumentedOption(string $setting, array $values): void
    {
        $store = file_get_contents(
            dirname(__DIR__, 2) . '/src/web/assets/src/vue/stores/AddressSettingsStore.js'
        );

        $this->assertMatchesRegularExpression("/'{$setting}':\\s*\\{([^}]*)\\}/", $store);
        preg_match("/'{$setting}':\\s*\\{([^}]*)\\}/", $store, $match);
        preg_match_all("/'(\\w+)'\\s*:/", $match[1], $keys);

        $this->assertSame($values, $keys[1]);
    }

    public static function optionProvider(): array
    {
        return [
            'mapOnStart' => ['mapOnStart', ['default', 'open', 'close']],
            'mapOnSearch' => ['mapOnSearch', ['open', 'close', 'noChange']],
            'visibilityToggle' => ['visibilityToggle', ['both', 'text', 'icon', 'hidden']],
            'coordinatesMode' => ['coordinatesMode', ['editable', 'readOnly', 'hidden']],
        ];
    }

    // ========================================================================= //
    // Methods Craft calls
    // ========================================================================= //

    public function testNormalizeValueReturnsAnAddressOrNull(): void
    {
        $type = $this->reflection->getMethod('normalizeValue')->getReturnType();

        $this->assertTrue($type->allowsNull());
        $this->assertSame(Address::class, $type->getName());
    }

    /**
     * @dataProvider overrideProvider
     */
    public function testOverridesTheFieldMethod(string $method): void
    {
        $this->assertSame(
            AddressField::class,
            $this->reflection->getMethod($method)->getDeclaringClass()->getName()
        );
    }

    public static function overrideProvider(): array
    {
        $methods = [
            'afterElementSave',
            'normalizeValue',
            'getElementValidationRules',
            'getInputHtml',
            'getSettingsHtml',
            'modifyElementsQuery',
            'getSearchKeywords',
        ];
        return array_combine($methods, array_map(static fn(string $m): array => [$m], $methods));
    }

    public function testValidationOnlyAppliesToRequiredFields(): void
    {
        $this->assertMatchesRegularExpression(
            '/if \(!\$this->required\) \{\s*return \[\];\s*\}[\s\S]{0,200}AddressValidator::class/',
            $this->source
        );
    }

    public function testTheInputPassesTheTokenAndDevModeToJavaScript(): void
    {
        $this->assertStringContainsString("window.mapboxAccessToken='{\$accessToken}';", $this->source);
        $this->assertStringContainsString('window.mapboxDevMode={$devMode};', $this->source);
    }

    public function testTheInputRegistersTheFieldBundle(): void
    {
        $this->assertStringContainsString('registerAssetBundle(AddressFieldAsset::class)', $this->source);
        $this->assertStringContainsString('registerAssetBundle(AddressFieldSettingsAsset::class)', $this->source);
    }

    public function testChineseIsMappedToSimplified(): void
    {
        // Mapbox has no plain `zh`.
        $this->assertStringContainsString("preg_replace('/^zh\$/', 'zh-Hans', \$language)", $this->source);
    }

    public function testMakiIconsArePublishedWithoutCacheBusting(): void
    {
        // The Vue side appends `/<icon>.svg` to this URL, so a `?v=` would break every icon.
        $this->assertStringContainsString(
            "\$images['maki'] = \$assetManager->getPublishedUrl(\$directory, true).'/images/maki';",
            $this->source
        );
    }

    public function testFieldIconsExistInDist(): void
    {
        $dist = dirname(__DIR__, 2) . '/src/web/assets/dist/images';

        foreach (['marker.svg', 'marker-hollow.svg', 'required.png'] as $image) {
            $this->assertStringContainsString("'{$image}'", $this->source);
            $this->assertFileExists("{$dist}/{$image}");
        }
    }
}
