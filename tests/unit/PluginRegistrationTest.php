<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\base\Plugin;
use doublesecretagency\mapbox\MapboxPlugin;
use doublesecretagency\mapbox\models\Settings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Structural tests for the plugin class and everything it registers.
 *
 * The plugin has no services and registers no components. Its `init()` is a
 * list of event wirings, so the shape of that list IS the plugin's behavior.
 * A dropped `Event::on` call is silent: the feature simply stops happening.
 */
class PluginRegistrationTest extends TestCase
{
    private ReflectionClass $reflection;
    private string $source;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(MapboxPlugin::class);
        $this->source = file_get_contents($this->reflection->getFileName());
    }

    /**
     * Get the body of `init()`.
     */
    private function _init(): string
    {
        $start = strpos($this->source, 'public function init(): void');
        $this->assertNotFalse($start);
        $end = strpos($this->source, 'protected function createSettingsModel', $start);
        $this->assertNotFalse($end);

        return substr($this->source, $start, $end - $start);
    }

    // ========================================================================= //
    // Plugin basics
    // ========================================================================= //

    public function testExtendsPlugin(): void
    {
        $this->assertTrue($this->reflection->isSubclassOf(Plugin::class));
    }

    public function testHasASettingsPage(): void
    {
        $this->assertTrue($this->reflection->getDefaultProperties()['hasCpSettings']);
    }

    public function testSchemaVersion(): void
    {
        // Bumping this without a migration (or the reverse) desyncs installs.
        $this->assertSame('1.1.0', $this->reflection->getDefaultProperties()['schemaVersion']);
    }

    public function testUsesTheSettingsModel(): void
    {
        $this->assertStringContainsString('return new Settings();', $this->source);
        $this->assertTrue(class_exists(Settings::class));
    }

    public function testComposerPointsAtThisClass(): void
    {
        $composer = json_decode(file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);

        $this->assertSame(MapboxPlugin::class, $composer['extra']['class']);
        $this->assertSame('mapbox', $composer['extra']['handle']);
    }

    // ========================================================================= //
    // init()
    // ========================================================================= //

    /**
     * @dataProvider registrarProvider
     */
    public function testInitCallsEachRegistrar(string $method): void
    {
        $this->assertTrue($this->reflection->hasMethod($method), "{$method}() is missing.");
        $this->assertStringContainsString("\$this->{$method}();", $this->_init());
    }

    public static function registrarProvider(): array
    {
        $methods = [
            '_registerFieldType',
            '_registerCompatibleFieldTypes',
            '_manageFieldTypeConversions',
            '_postInstallRedirect',
            '_normalizeSubfieldConfig',
        ];
        return array_combine($methods, array_map(static fn(string $m): array => [$m], $methods));
    }

    public function testInitRegistersTheTwigExtension(): void
    {
        $this->assertStringContainsString('registerTwigExtension(new Extension())', $this->_init());
    }

    public function testInitSetsTheStaticInstance(): void
    {
        $this->assertStringContainsString('self::$plugin = $this;', $this->_init());
    }

    // ========================================================================= //
    // Event wiring
    // ========================================================================= //

    /**
     * @dataProvider eventProvider
     */
    public function testRegistersEvent(string $pattern): void
    {
        $this->assertMatchesRegularExpression($pattern, $this->source);
    }

    public static function eventProvider(): array
    {
        return [
            'field type' => ['/Fields::EVENT_REGISTER_FIELD_TYPES[\s\S]{0,200}\$event->types\[\] = AddressField::class;/'],
            'compatible types' => ['/Fields::EVENT_DEFINE_COMPATIBLE_FIELD_TYPES[\s\S]{0,400}\$event->compatibleTypes\[\] = AddressField::class;/'],
            'conversion' => ['/ProjectConfig::EVENT_UPDATE_ITEM/'],
            'post-install' => ['/Plugins::EVENT_AFTER_INSTALL_PLUGIN/'],
            'subfield typecast' => ['/Field::EVENT_BEFORE_SAVE[\s\S]{0,500}AddressField::typecastSubfieldConfig\(/'],
        ];
    }

    public function testCompatibilityOnlyAppliesToTheGoogleMapsField(): void
    {
        $this->assertStringContainsString(
            "is_a(\$event->field, 'doublesecretagency\\googlemaps\\fields\\AddressField')",
            $this->source
        );
    }

    public function testCompatibilityIsSkippedOnCraftVersionsWithoutTheEvent(): void
    {
        $this->assertStringContainsString('if (!class_exists(DefineCompatibleFieldTypesEvent::class))', $this->source);
    }

    public function testPostInstallRedirectSkipsConsoleInstalls(): void
    {
        $this->assertMatchesRegularExpression(
            '/getIsConsoleRequest\(\)\) \{\s*return;\s*\}[\s\S]{0,200}settings\/plugins\/mapbox/',
            $this->source
        );
    }
}
