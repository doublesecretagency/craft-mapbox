<?php
namespace doublesecretagency\mapbox\tests\unit;

use craft\elements\Entry;
use doublesecretagency\mapbox\enums\Defaults;
use doublesecretagency\mapbox\fields\AddressField;
use doublesecretagency\mapbox\models\Address;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Pure-unit tests for the Address field's subfield configuration handling.
 *
 * Two shapes exist in the wild. Fields saved since 1.1 store a sequential list
 * of subfield settings. Older fields store an associative array keyed by handle
 * with a `position`. Every field is normalized to the list shape when it is
 * rendered, so a regression here breaks every legacy field at once.
 *
 * The normalizer is a private instance method that touches nothing but its
 * argument and `Defaults`, so it is invoked on an instance built without a
 * constructor.
 */
class AddressFieldNormalizationTest extends TestCase
{
    private AddressField $field;
    private ReflectionMethod $normalize;

    protected function setUp(): void
    {
        $this->field = (new ReflectionClass(AddressField::class))->newInstanceWithoutConstructor();
        $this->normalize = new ReflectionMethod(AddressField::class, '_normalizeSubfieldConfig');
        $this->normalize->setAccessible(true);
    }

    private function _normalize(array $config): array
    {
        return $this->normalize->invoke($this->field, $config);
    }

    // ========================================================================= //
    // typecastSubfieldConfig
    // ========================================================================= //

    public function testTypecastCastsEveryKey(): void
    {
        $config = [[
            'handle' => 'city',
            'label' => 'Town',
            'width' => '50',
            'enabled' => '1',
            'autocomplete' => 0,
            'required' => 'yes',
        ]];

        AddressField::typecastSubfieldConfig($config);

        $this->assertSame([[
            'handle' => 'city',
            'label' => 'Town',
            'width' => 50,
            'enabled' => true,
            'autocomplete' => false,
            'required' => true,
        ]], $config);
    }

    public function testTypecastFillsMissingKeys(): void
    {
        $config = [[]];

        AddressField::typecastSubfieldConfig($config);

        $this->assertSame([[
            'handle' => '',
            'label' => '',
            'width' => 100,
            'enabled' => false,
            'autocomplete' => false,
            'required' => false,
        ]], $config);
    }

    public function testTypecastDropsUnknownKeys(): void
    {
        // An old `position` key must not survive into the new shape.
        $config = [['handle' => 'city', 'position' => 3]];

        AddressField::typecastSubfieldConfig($config);

        $this->assertArrayNotHasKey('position', $config[0]);
    }

    public function testTypecastWorksInPlace(): void
    {
        $param = (new ReflectionMethod(AddressField::class, 'typecastSubfieldConfig'))->getParameters()[0];

        $this->assertTrue($param->isPassedByReference());
    }

    // ========================================================================= //
    // New style (sequential list)
    // ========================================================================= //

    public function testTheDefaultsPassThroughUnchanged(): void
    {
        $this->assertSame(Defaults::SUBFIELDCONFIG, $this->_normalize(Defaults::SUBFIELDCONFIG));
    }

    public function testACustomOrderIsKept(): void
    {
        $reversed = array_reverse(Defaults::SUBFIELDCONFIG);

        $this->assertSame(
            array_column($reversed, 'handle'),
            array_column($this->_normalize($reversed), 'handle')
        );
    }

    public function testMissingSubfieldsAreAppendedInDefaultOrder(): void
    {
        $result = $this->_normalize([
            ['handle' => 'country', 'label' => 'Land', 'width' => '100', 'enabled' => 1],
        ]);

        $expected = array_merge(
            ['country'],
            array_values(array_diff(array_column(Defaults::SUBFIELDCONFIG, 'handle'), ['country']))
        );

        $this->assertSame($expected, array_column($result, 'handle'));
    }

    public function testExistingSubfieldsAreTypecastButKeepTheirValues(): void
    {
        $result = $this->_normalize([
            ['handle' => 'country', 'label' => 'Land', 'width' => '40', 'enabled' => 1],
        ]);

        $this->assertSame([
            'handle' => 'country',
            'label' => 'Land',
            'width' => 40,
            'enabled' => true,
            'autocomplete' => false,
            'required' => false,
        ], $result[0]);
    }

    public function testNothingIsDuplicated(): void
    {
        $handles = array_column($this->_normalize(Defaults::SUBFIELDCONFIG), 'handle');

        $this->assertSame($handles, array_values(array_unique($handles)));
    }

    // ========================================================================= //
    // Old style (associative, keyed by handle)
    // ========================================================================= //

    public function testOldStyleConvertsToTheListShape(): void
    {
        $result = $this->_normalize(['street1' => ['label' => 'Street', 'position' => 1]]);

        $this->assertTrue(array_is_list($result));
        $this->assertCount(count(Defaults::SUBFIELDCONFIG), $result);
        $this->assertEqualsCanonicalizing(
            array_column(Defaults::SUBFIELDCONFIG, 'handle'),
            array_column($result, 'handle')
        );
    }

    public function testOldStyleSortsByPosition(): void
    {
        $result = $this->_normalize([
            'street1' => ['position' => 2],
            'city' => ['position' => 1],
        ]);

        $this->assertSame(['city', 'street1'], array_slice(array_column($result, 'handle'), 0, 2));
    }

    public function testOldStyleKeepsItsLabelAndWidth(): void
    {
        $result = $this->_normalize(['street1' => ['label' => 'Street', 'width' => '80', 'position' => 1]]);
        $street = array_values(array_filter($result, static fn(array $s): bool => 'street1' === $s['handle']))[0];

        $this->assertSame('Street', $street['label']);
        $this->assertSame(80, $street['width']);
    }

    public function testOldStyleFallsBackToDefaultLabelAndWidth(): void
    {
        $result = $this->_normalize(['street1' => ['position' => 1]]);
        $city = array_values(array_filter($result, static fn(array $s): bool => 'city' === $s['handle']))[0];

        $this->assertSame('City', $city['label']);
        $this->assertSame(50, $city['width']);
    }

    public function testOldStyleSubfieldsStartDisabled(): void
    {
        // Unlike a new field, nothing is enabled unless the old config said so.
        $result = $this->_normalize(['street1' => ['enabled' => true, 'position' => 1]]);

        $this->assertSame(['street1'], array_column(
            array_filter($result, static fn(array $s): bool => $s['enabled']),
            'handle'
        ));
    }

    // ========================================================================= //
    // Misconfigured
    // ========================================================================= //

    public function testAnEmptyConfigReturnsTheDefaults(): void
    {
        $this->assertSame(Defaults::SUBFIELDCONFIG, $this->_normalize([]));
    }

    public function testAnUnrecognizedShapeReturnsTheDefaults(): void
    {
        $this->assertSame(Defaults::SUBFIELDCONFIG, $this->_normalize(['foo' => 'bar']));
    }

    // ========================================================================= //
    // normalizeValue (array values)
    // ========================================================================= //

    /**
     * Build an entry on a given site without booting Craft.
     */
    private function _entry(int $id, int $siteId): Entry
    {
        $entry = (new ReflectionClass(Entry::class))->newInstanceWithoutConstructor();
        $entry->id = $id;
        $entry->siteId = $siteId;
        return $entry;
    }

    /**
     * Matrix, duplicate, copy-to-site and draft flows normalize an array that
     * still carries the SOURCE element's IDs. The element being normalized
     * must win, or address.element and address.siteId point at the source.
     */
    public function testAnArrayValueTakesTheIdsOfTheElementBeingNormalized(): void
    {
        $this->field->id = 7;
        $address = $this->field->normalizeValue(
            ['elementId' => 100, 'siteId' => 1, 'fieldId' => 99, 'city' => 'Austin'],
            $this->_entry(200, 2)
        );

        $this->assertSame(200, $address->elementId);
        $this->assertSame(2, $address->siteId);
        $this->assertSame(7, $address->fieldId);
        $this->assertSame('Austin', $address->city);
    }

    /**
     * An unsaved element still wins: its null ID beats an ID the value
     * carried in from somewhere else.
     */
    public function testAnArrayValueOnAnUnsavedElementTakesItsNullId(): void
    {
        $this->field->id = 7;
        $entry = $this->_entry(1, 2);
        $entry->id = null;
        $address = $this->field->normalizeValue(['elementId' => 100, 'siteId' => 1], $entry);

        $this->assertNull($address->elementId);
        $this->assertSame(2, $address->siteId);
    }

    /**
     * Propagation, drafts and duplicates hand over the source element's
     * model. The element being normalized gets a copy with its own IDs, and
     * the source's model is left alone.
     */
    public function testAModelFromAnotherElementGetsACopyWithThisElementsIds(): void
    {
        $this->field->id = 7;
        $source = new Address(['id' => 55, 'elementId' => 100, 'siteId' => 1, 'fieldId' => 7, 'city' => 'Austin']);
        $address = $this->field->normalizeValue($source, $this->_entry(100, 2));

        $this->assertNotSame($source, $address);
        // The id is kept: maps use it for marker IDs, and saving doesn't
        $this->assertSame([55, 100, 2, 7, 'Austin'], [$address->id, $address->elementId, $address->siteId, $address->fieldId, $address->city]);
        $this->assertSame([55, 1], [$source->id, $source->siteId]);
    }

    public function testAModelForThisElementIsReturnedAsIs(): void
    {
        $this->field->id = 7;
        $model = new Address(['elementId' => 100, 'siteId' => 2, 'fieldId' => 7]);

        $this->assertSame($model, $this->field->normalizeValue($model, $this->_entry(100, 2)));
        $this->assertSame($model, $this->field->normalizeValue($model));
    }

    public function testAnArrayValueWithoutAnElementKeepsItsOwnIds(): void
    {
        $this->field->id = null;
        $address = $this->field->normalizeValue(['elementId' => 100, 'siteId' => 3, 'fieldId' => 99]);

        $this->assertSame(100, $address->elementId);
        $this->assertSame(3, $address->siteId);
        $this->assertSame(99, $address->fieldId);
    }

    /**
     * A missing ID stays null. Casting it to 0 would name a site, element or
     * field that doesn't exist, and `is null` checks would never match.
     */
    public function testAnArrayValueWithNoIdsLeavesThemNull(): void
    {
        $this->field->id = null;
        $address = $this->field->normalizeValue(['city' => 'Austin']);

        $this->assertNull($address->elementId);
        $this->assertNull($address->siteId);
        $this->assertNull($address->fieldId);
    }
}
