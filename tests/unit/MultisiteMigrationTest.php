<?php
namespace doublesecretagency\mapbox\tests\unit;

use doublesecretagency\mapbox\migrations\m240530_122024_multisite_support;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Structural tests for the multisite migration (#5).
 *
 * Ported from Google Maps' MultisiteMigrationTest (its #139 fixes). Like Google
 * Maps on Craft 5, Mapbox before 2.2.0 also serialized every Address into
 * `elements_sites.content` (its `hasContentColumn()` is a Craft 4 API that Craft 5
 * never calls), and read it back from there. So a translatable field could show
 * a different Address on each site while the table kept a single row. The
 * migration restores each site's own Address from that content first.
 *
 * What the fixes guard against, all reproduced in Google Maps on 2026-11-06:
 *
 * - Before multisite support nothing prevented two rows for the same element
 *   and field. A leftover duplicate given the same site as its twin collides
 *   with the unique (elementId, siteId, fieldId) index.
 * - On MySQL a failed run is not rolled back (DDL commits implicitly), so a
 *   retry must skip whatever the failed run already created.
 * - Loading every row with `->all()` ran large sites out of memory.
 * - On Postgres, `LIKE` against the `jsonb` columns `fieldlayouts.config` and
 *   `elements_sites.content` is an error.
 *
 * The live checks need a database (verified on MySQL and Postgres 2026-11-10),
 * so these tests pin the shape of each fix.
 */
class MultisiteMigrationTest extends TestCase
{
    private ReflectionClass $reflection;
    private string $source;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(m240530_122024_multisite_support::class);
        $this->source = file_get_contents($this->reflection->getFileName());
    }

    private function _methodBody(string $method): string
    {
        $reflection = $this->reflection->getMethod($method);
        $lines = file($this->reflection->getFileName());

        return implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }

    // ========================================================================= //
    // Order of operations
    // ========================================================================= //

    public function testDuplicatesAreRemovedBeforeTheIndexesAreBuilt(): void
    {
        $safeUp = $this->_methodBody('safeUp');

        $columnsAt = strpos($safeUp, '_newColumns()');
        $removeAt = strpos($safeUp, '_removeDuplicates()');
        $indexesAt = strpos($safeUp, '_newIndexes()');

        $this->assertNotFalse($removeAt, 'safeUp() no longer removes duplicate Addresses.');
        $this->assertLessThan($removeAt, $columnsAt, 'The siteId column must exist before duplicates are found by it.');
        $this->assertLessThan($indexesAt, $removeAt, 'Duplicates must be gone before the unique index is built.');
    }

    public function testTheUniqueIndexGuardsEveryCopy(): void
    {
        // Indexes come before populating, so the unique key rejects any copy that would collide
        $safeUp = $this->_methodBody('safeUp');

        $this->assertLessThan(strpos($safeUp, '_populateData()'), strpos($safeUp, '_newIndexes()'));
    }

    public function testTheSiteBecomesRequiredOnlyOnceEveryRowHasOne(): void
    {
        $safeUp = $this->_methodBody('safeUp');

        $populateAt = strpos($safeUp, '_populateData()');
        $notNullAt = strpos($safeUp, "alterColumn(Install::MAPBOX_ADDRESSES, 'siteId', \$this->integer()->notNull())");
        $foreignKeyAt = strpos($safeUp, '_newForeignKeys()');

        $this->assertNotFalse($notNullAt);
        $this->assertLessThan($notNullAt, $populateAt);
        $this->assertLessThan($foreignKeyAt, $notNullAt);
    }

    // ========================================================================= //
    // Duplicate rows
    // ========================================================================= //

    public function testOnlyUnassignedDuplicatesAreRemoved(): void
    {
        // Rows that already have a site are unique by construction
        $body = $this->_methodBody('_removeDuplicates');

        $this->assertStringContainsString("'[[siteId]]' => null", $body);
        $this->assertStringContainsString("having('COUNT(*) > 1')", $body);
    }

    public function testTheMostRecentlyUpdatedDuplicateIsKept(): void
    {
        $body = $this->_methodBody('_removeDuplicates');

        $this->assertStringContainsString("'[[dateUpdated]]' => SORT_DESC, '[[id]]' => SORT_DESC", $body);
        $this->assertStringContainsString('array_shift($ids);', $body);
    }

    public function testAssigningASiteGoesThroughTheCollisionCheck(): void
    {
        // A failed earlier run can already have given a twin a site
        $assign = $this->_methodBody('_assignSites');

        $this->assertStringContainsString("['not', ['[[siteId]]' => null]]", $assign);

        $existsAt = strpos($assign, '->exists()');
        $deleteAt = strpos($assign, '$this->delete(');
        $updateAt = strpos($assign, '$this->update(');

        $this->assertNotFalse($existsAt);
        $this->assertLessThan($updateAt, $deleteAt, 'A stale copy must be deleted, not assigned.');
        $this->assertLessThan($deleteAt, $existsAt);
    }

    public function testRowsAreAssignedOneAtATime(): void
    {
        // A single UPDATE of every NULL row would give duplicates the same site
        $this->assertStringContainsString("['[[id]]' => \$address['id']]", $this->_methodBody('_assignSites'));
    }

    // ========================================================================= //
    // Each site's own Address, from content
    // ========================================================================= //

    public function testContentIsRestoredBeforeAnythingElse(): void
    {
        // What each site was showing came from its content, so it outranks the table's single row
        $populate = $this->_methodBody('_populateData');

        $restoreAt = strpos($populate, '_restoreFromContent()');
        $this->assertNotFalse($restoreAt, 'Addresses are no longer restored from content.');
        $this->assertLessThan(strpos($populate, '_assignSites()'), $restoreAt);
    }

    public function testRestoringTakesTheSitesValuesNotJustItsId(): void
    {
        // Reusing the unassigned row must also replace its values with this site's
        $body = $this->_methodBody('_restoreLayoutElement');

        $this->assertStringContainsString("['[[siteId]]' => \$siteId] + \$values", $body);
        $this->assertStringContainsString('] + $values);', $body);
    }

    public function testRestoringSkipsASiteThatAlreadyHasAnAddress(): void
    {
        // A retry must not overwrite or duplicate what an earlier run restored
        $body = $this->_methodBody('_restoreLayoutElement');

        $existsAt = strpos($body, '->exists()');
        $this->assertNotFalse($existsAt);
        $this->assertLessThan(strpos($body, '$this->update('), $existsAt);
        $this->assertLessThan(strpos($body, '$this->insert('), $existsAt);
    }

    public function testOnlyLiveLayoutElementsAreRestored(): void
    {
        // Content can keep keys from old layouts, which Craft no longer reads either
        $body = $this->_methodBody('_restoreFromContent');

        $this->assertStringContainsString("['[[type]]' => AddressField::class]", $body);
        $this->assertStringContainsString("(\$layoutElement['fieldUid'] ?? null) === \$field['uid']", $body);
    }

    public function testMissingCoordinatesStayEmpty(): void
    {
        // A cast would turn a missing coordinate into 0, a real place in the ocean
        $body = $this->_methodBody('_addressValues');

        $this->assertStringContainsString('is_numeric($address[$key] ?? null)', $body);
        $this->assertDoesNotMatchRegularExpression("/=> \\(float\\) \\\$address\\['l(at|ng)'\\]/", $body);
    }

    public function testEveryLikeSearchCastsJsonForPostgres(): void
    {
        $this->assertSame(0, preg_match("/\\['like', '\\[\\[/", $this->source), 'A LIKE on a raw JSON column fails on Postgres.');
        $this->assertSame(2, substr_count($this->source, "['like', \$this->_searchable("));

        $searchable = $this->_methodBody('_searchable');
        $this->assertStringContainsString('getIsPgsql()', $searchable);
        $this->assertStringContainsString('AS TEXT', $searchable);
    }

    // ========================================================================= //
    // Every site keeps the Address
    // ========================================================================= //

    public function testEverySiteOfTheElementGetsACopy(): void
    {
        // Before multisite support one Address was shared by every site, so no site may lose it
        $populate = $this->_methodBody('_populateData');
        $assignAt = strpos($populate, '_assignSites()');
        $fillAt = strpos($populate, '_fillMissingSites()');

        $this->assertNotFalse($fillAt, 'Addresses are no longer copied to the other sites.');
        $this->assertLessThan($fillAt, $assignAt, 'Copies must be made after every row has a site.');

        $fill = $this->_methodBody('_fillMissingSites');
        $this->assertStringContainsString('unset($copy[\'id\']);', $fill, 'A copy must not reuse the original ID.');
        $this->assertStringContainsString('StringHelper::UUID()', $fill);
        $this->assertStringContainsString('($rows[$primarySiteId] ?? reset($rows))', $fill);
    }

    public function testThePrimarySiteComesFirst(): void
    {
        $this->assertStringContainsString('usort($siteIds', $this->_methodBody('_elementSiteIds'));
        $this->assertStringContainsString('_elementSiteIds((int) $address[\'elementId\'], $primarySiteId)', $this->_methodBody('_assignSites'));
    }

    public function testAnElementOnNoSiteFallsBackToThePrimarySite(): void
    {
        $this->assertStringContainsString('($siteIds[0] ?? $primarySiteId)', $this->_methodBody('_assignSites'));
    }

    // ========================================================================= //
    // Retrying after a failed run
    // ========================================================================= //

    public function testIndexesAreOnlyCreatedWhenMissing(): void
    {
        $body = $this->_methodBody('_newIndexes');

        $this->assertStringContainsString('Db::findIndex(Install::MAPBOX_ADDRESSES, $columns, $unique, $this->db)', $body);
        $this->assertSame(1, substr_count($body, 'createIndex('), 'Every index should go through the existence check.');
    }

    public function testTheMigrationAddsTheSameIndexesAsTheInstall(): void
    {
        $body = $this->_methodBody('_newIndexes');

        foreach ([
            "[['siteId'], false]",
            "[['siteId', 'fieldId'], false]",
            "[['elementId', 'siteId'], false]",
            "[['elementId', 'fieldId'], false]",
            "[['elementId', 'siteId', 'fieldId'], true]",
        ] as $index) {
            $this->assertStringContainsString($index, $body);
        }
    }

    public function testTheForeignKeyIsOnlyCreatedWhenMissing(): void
    {
        $this->assertStringContainsString(
            "Db::findForeignKey(Install::MAPBOX_ADDRESSES, ['siteId'], \$this->db)",
            $this->_methodBody('_newForeignKeys')
        );
    }

    public function testTheSiteColumnIsOnlyAddedWhenMissing(): void
    {
        $this->assertStringContainsString("columnExists(Install::MAPBOX_ADDRESSES, 'siteId')", $this->_methodBody('_newColumns'));
    }

    // ========================================================================= //
    // Postgres and large sites
    // ========================================================================= //

    public function testNoMysqlOnlySyntax(): void
    {
        // PHP's own spaceship operator is fine; only a quoted SQL fragment may not use it
        $this->assertDoesNotMatchRegularExpression('/[\'"][^\'"\n]*<=>[^\'"\n]*[\'"]/', $this->source);
        $this->assertDoesNotMatchRegularExpression('/DELETE\s+\w+\s+FROM/i', $this->source);
    }

    /**
     * @dataProvider streamedMethodProvider
     */
    public function testLargeTablesAreReadInBatches(string $method, string $query): void
    {
        // The table-wide query streams; a per-element lookup inside the loop may load its few rows
        $body = $this->_methodBody($method);

        $this->assertStringContainsString("foreach (Db::each(\${$query}) as", $body);
        $this->assertDoesNotMatchRegularExpression('/\\$' . $query . ' = \\(new Query\\(\\)\\)[^;]*->all\\(\\);/', $body);
    }

    public static function streamedMethodProvider(): array
    {
        return [
            '_assignSites' => ['_assignSites', 'addresses'],
            '_fillMissingSites' => ['_fillMissingSites', 'pairs'],
            '_restoreLayoutElement' => ['_restoreLayoutElement', 'elementSites'],
        ];
    }

    // ========================================================================= //

    public function testItCannotBeReverted(): void
    {
        $this->assertStringContainsString('return false;', $this->_methodBody('safeDown'));
    }
}
