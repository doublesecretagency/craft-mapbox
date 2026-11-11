<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\db\ActiveRecord;
use doublesecretagency\mapbox\helpers\Mapbox;
use doublesecretagency\mapbox\migrations\Install;
use doublesecretagency\mapbox\records\Address as AddressRecord;
use doublesecretagency\mapbox\validators\AddressValidator;
use doublesecretagency\mapbox\web\twig\Extension;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use yii\validators\Validator;

/**
 * Structural tests for the classes Craft calls or templates reach directly.
 *
 * That means the Twig extension, the record and the validator. A renamed
 * method or changed access level on any of them is a break with no local
 * caller to catch it.
 *
 * The validator's main path looks the field up through Craft, so it is pinned
 * at the source level. Its error message builder is a pure private method and
 * is called directly.
 */
class ControlPanelSurfaceTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents((new ReflectionClass(AddressValidator::class))->getFileName());
    }

    private function _message(array $subfields, bool $coords): string
    {
        $method = new ReflectionMethod(AddressValidator::class, '_getMessage');
        $method->setAccessible(true);

        return $method->invoke(new AddressValidator(), $subfields, $coords);
    }

    // ========================================================================= //
    // Twig extension
    // ========================================================================= //

    public function testTwigExtensionProvidesGlobals(): void
    {
        $this->assertTrue(is_subclass_of(Extension::class, AbstractExtension::class));
        $this->assertTrue(is_subclass_of(Extension::class, GlobalsInterface::class));
    }

    public function testTwigGlobalIsNamedMapbox(): void
    {
        $this->assertSame(['mapbox'], array_keys((new Extension())->getGlobals()));
    }

    public function testTwigGlobalIsTheStaticHelperFacade(): void
    {
        $this->assertInstanceOf(Mapbox::class, (new Extension())->getGlobals()['mapbox']);
    }

    // ========================================================================= //
    // Record
    // ========================================================================= //

    public function testRecordExtendsActiveRecord(): void
    {
        $this->assertTrue(is_subclass_of(AddressRecord::class, ActiveRecord::class));
    }

    public function testRecordTableNameMatchesTheInstallMigration(): void
    {
        $install = file_get_contents((new ReflectionClass(Install::class))->getFileName());

        $this->assertSame('{{%mapbox_addresses}}', AddressRecord::tableName());
        $this->assertSame(AddressRecord::tableName(), Install::MAPBOX_ADDRESSES);
        $this->assertStringContainsString('createTable(static::MAPBOX_ADDRESSES', $install);
    }

    // ========================================================================= //
    // Messages
    // ========================================================================= //

    public function testListsMissingSubfields(): void
    {
        $this->assertSame(
            'The following subfields are required: **City**, **Zip Code**',
            $this->_message(['**City**', '**Zip Code**'], false)
        );
    }

    public function testAppendsCoordinatesToTheSubfieldList(): void
    {
        $this->assertSame(
            'The following subfields are required: **City**, **Longitude** & **Latitude**',
            $this->_message(['**City**'], true)
        );
    }

    public function testCoordinatesAloneGetTheirOwnMessage(): void
    {
        $this->assertSame('A valid set of coordinates is required.', $this->_message([], true));
    }

    // ========================================================================= //
    // Validator (source-level)
    // ========================================================================= //

    public function testValidatorExtendsYiiValidator(): void
    {
        $this->assertTrue(is_subclass_of(AddressValidator::class, Validator::class));
    }

    public function testStripsTheFieldPrefixFromTheAttribute(): void
    {
        $this->assertStringContainsString("preg_replace('/^field:/', '', \$attribute)", $this->source);
    }

    public function testOnlyEnabledRequiredSubfieldsAreChecked(): void
    {
        $this->assertMatchesRegularExpression(
            '/if \(!\$enabled\) \{\s*continue;\s*\}[\s\S]*?if \(!\$required\) \{\s*continue;\s*\}/',
            $this->source
        );
    }

    public function testCoordinatesAreRequiredByDefault(): void
    {
        $this->assertStringContainsString("(bool) (\$field->requireCoordinates ?? true)", $this->source);
    }

    public function testSubfieldLabelsAreBolded(): void
    {
        // The CP renders the error as Markdown.
        $this->assertStringContainsString('"**{$label}**"', $this->source);
    }
}
