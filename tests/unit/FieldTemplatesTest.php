<?php
namespace doublesecretagency\mapbox\tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * Source-level tests for the plugin's Twig templates and the JS they hand off to.
 *
 * Each Address field renders a tiny template that pushes its config onto a
 * global and calls a function the compiled Vue bundle defines on `window`.
 * Those names are the whole contract between Twig and Vue, and a rename on
 * either side leaves an empty field with no error.
 *
 * These read templates as text. They cannot tell whether a template parses.
 */
class FieldTemplatesTest extends TestCase
{
    private function _template(string $path): string
    {
        $full = dirname(__DIR__, 2) . "/src/templates/{$path}";
        $this->assertFileExists($full);

        return file_get_contents($full);
    }

    private function _src(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2) . "/src/{$path}");
    }

    // ========================================================================= //
    // Every template exists
    // ========================================================================= //

    /**
     * @dataProvider templateProvider
     */
    public function testTemplateExists(string $path): void
    {
        $this->assertFileExists(dirname(__DIR__, 2) . "/src/templates/{$path}");
    }

    public static function templateProvider(): array
    {
        $paths = [
            'address.twig',
            'address-settings.twig',
            'settings.twig',
            'maps/popup-error.twig',
            '_form/autosuggest.twig',
            '_form/select.twig',
        ];
        return array_combine($paths, array_map(static fn(string $p): array => [$p], $paths));
    }

    public function testEveryRenderedTemplateExists(): void
    {
        // Every `mapbox/<path>` a PHP file renders must be on disk.
        $php = $this->_src('MapboxPlugin.php') . $this->_src('fields/AddressField.php') . $this->_src('models/DynamicMap.php');
        preg_match_all("/renderTemplate\\('mapbox\\/([\\w\\/-]+)'/", $php, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $path) {
            $this->assertFileExists(dirname(__DIR__, 2) . "/src/templates/{$path}.twig");
        }
    }

    // ========================================================================= //
    // Address field
    // ========================================================================= //

    public function testFieldTemplatePushesItsConfig(): void
    {
        $this->assertStringContainsString('addressFieldConfigs.push({{ config|json_encode|raw }});', $this->_template('address.twig'));
    }

    public function testFieldTemplateInitializesOnLoadAndOnSecondaryLoads(): void
    {
        $twig = $this->_template('address.twig');

        $this->assertStringContainsString("if ('function' === typeof initAddressField)", $twig);
        $this->assertStringContainsString("{% js 'initAddressField();' %}", $twig);
    }

    public function testTheFieldEntryPointDefinesTheSameNames(): void
    {
        $js = $this->_src('web/assets/src/js/address.js');

        $this->assertStringContainsString('window.initAddressField = ', $js);
        $this->assertStringContainsString('addressFieldConfigs', $js);
        $this->assertStringContainsString('document.getElementById(config.namespace.id)', $js);
    }

    public function testTheFieldMountsOnTheNamespacedHandle(): void
    {
        $this->assertStringContainsString('<div id="{{ config.namespace.handle }}"></div>', $this->_template('address.twig'));
    }

    // ========================================================================= //
    // Address field settings
    // ========================================================================= //

    public function testSettingsTemplateWaitsForTheSearchLibrary(): void
    {
        $twig = $this->_template('address-settings.twig');

        $this->assertStringContainsString('if (window.mapboxsearchcore)', $twig);
        $this->assertStringContainsString('window.initAddressFieldSettings();', $twig);
    }

    public function testSettingsContainerClassMatchesTheEntryPoint(): void
    {
        $this->assertStringContainsString('class="address-settings-container"', $this->_template('address-settings.twig'));
        $this->assertStringContainsString("querySelectorAll('.address-settings-container')", $this->_src('web/assets/src/js/address-settings.js'));
    }

    public function testTheSettingsEntryPointDefinesItsInitializer(): void
    {
        $this->assertStringContainsString('window.initAddressFieldSettings = ', $this->_src('web/assets/src/js/address-settings.js'));
    }

    public function testSettingsUsePlaceholderNamespacing(): void
    {
        // The Vue store swaps `PLACEHOLDER` for each setting's name.
        $this->assertStringContainsString("namespaceInputName('PLACEHOLDER')", $this->_src('fields/AddressField.php'));
        $this->assertStringContainsString('replace(/\[PLACEHOLDER]/, replacement)', $this->_src('web/assets/src/vue/stores/AddressSettingsStore.js'));
    }

    // ========================================================================= //
    // Plugin settings
    // ========================================================================= //

    public function testSettingsPageAsksForTheAccessToken(): void
    {
        $twig = $this->_template('settings.twig');

        $this->assertStringContainsString("name: 'accessToken'", $twig);
        $this->assertStringContainsString("{% set autosuggest = 'mapbox/_form/autosuggest' %}", $twig);
    }

    public function testAutosuggestDisablesSettingsSetInTheConfigFile(): void
    {
        $twig = $this->_template('_form/autosuggest.twig');

        $this->assertStringContainsString('{% set configOverride = (key in configFile|keys) %}', $twig);
        $this->assertStringContainsString('disabled: configOverride,', $twig);
        $this->assertStringContainsString('suggestEnvVars: not configOverride', $twig);
    }

    public function testWelcomeModeFollowsTheInstallRedirect(): void
    {
        $this->assertStringContainsString("getQueryParam('welcome')", $this->_template('settings.twig'));
        $this->assertStringContainsString("['welcome' => 1]", $this->_src('MapboxPlugin.php'));
    }

    // ========================================================================= //
    // Popup errors
    // ========================================================================= //

    public function testPopupErrorUsesTheVariablesDynamicMapPasses(): void
    {
        $twig = $this->_template('maps/popup-error.twig');
        $php = $this->_src('models/DynamicMap.php');

        $this->assertStringContainsString('{{ filename }}', $twig);
        $this->assertStringContainsString('{{ message }}', $twig);
        $this->assertMatchesRegularExpression("/'mapbox\\/maps\\/popup-error', \\[\\s*'filename' => \\\$filename,\\s*'message' => \\\$e->getMessage\\(\\),/", $php);
    }

    public function testPopupErrorRendersFromTheControlPanelTemplates(): void
    {
        // A site template can't see `mapbox/...`, so the error renders in CP mode.
        $this->assertStringContainsString('View::TEMPLATE_MODE_CP', $this->_src('models/DynamicMap.php'));
    }
}
