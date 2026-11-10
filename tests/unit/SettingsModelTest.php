<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\base\Model;
use doublesecretagency\mapbox\models\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the plugin settings and the config file template that mirrors them.
 *
 * `src/config.php` is the file users copy to `config/mapbox.php`. A setting
 * missing from it is undiscoverable, and one listed there but absent from the
 * model is silently ignored, so the two must agree in both directions.
 */
class SettingsModelTest extends TestCase
{
    private const SETTINGS = ['accessToken', 'enableJsLogging', 'minifyJsFiles', 'fieldParams'];

    private function _config(): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/src/config.php');
    }

    // ========================================================================= //
    // Model
    // ========================================================================= //

    public function testExtendsModel(): void
    {
        $this->assertTrue(is_subclass_of(Settings::class, Model::class));
    }

    public function testHasExactlyTheKnownSettings(): void
    {
        $this->assertSame(self::SETTINGS, array_keys((new Settings())->getAttributes()));
    }

    public function testDefaults(): void
    {
        $settings = new Settings();

        $this->assertNull($settings->accessToken);
        $this->assertTrue($settings->enableJsLogging);
        $this->assertFalse($settings->minifyJsFiles);
        $this->assertSame([], $settings->fieldParams);
    }

    // ========================================================================= //
    // Config file template
    // ========================================================================= //

    public function testConfigTemplateListsEverySetting(): void
    {
        // Top-level keys only: nested `fieldParams` keys sit after `//    `.
        preg_match_all("/^    \\/\\/'(\\w+)'\\s*=>/m", $this->_config(), $matches);

        $this->assertSame(self::SETTINGS, $matches[1]);
    }

    public function testConfigTemplateReadsTheTokenFromTheEnvironment(): void
    {
        $this->assertStringContainsString("getenv('MAPBOX_ACCESSTOKEN')", $this->_config());
    }

    public function testConfigTemplateDefaultsMatchTheModel(): void
    {
        $config = $this->_config();

        $this->assertStringContainsString("//'enableJsLogging' => true,", $config);
        $this->assertStringContainsString("//'minifyJsFiles' => false,", $config);
    }

    /**
     * Every documented `fieldParams` key is read by the Address field's search.
     *
     * @dataProvider fieldParamProvider
     */
    public function testEachFieldParamIsUsedByTheSearch(string $key): void
    {
        $this->assertMatchesRegularExpression("/^    \\/\\/    '{$key}'\\s*=>/m", $this->_config());

        $vue = file_get_contents(
            dirname(__DIR__, 2) . '/src/web/assets/src/vue/address/address-subfields.vue'
        );

        $this->assertStringContainsString("fieldParams.{$key}", $vue);
    }

    public static function fieldParamProvider(): array
    {
        return [
            'limit' => ['limit'],
            'language' => ['language'],
            'country' => ['country'],
        ];
    }

    public function testTheSearchCapsTheLimitAtTen(): void
    {
        $vue = file_get_contents(
            dirname(__DIR__, 2) . '/src/web/assets/src/vue/address/address-subfields.vue'
        );

        $this->assertStringContainsString("options['limit'] = (fieldParams.limit || 6);", $vue);
        $this->assertMatchesRegularExpression("/if \\(10 < options\\['limit'\\]\\) \\{[\\s\\S]{0,200}options\\['limit'\\] = 10;/", $vue);
    }
}
