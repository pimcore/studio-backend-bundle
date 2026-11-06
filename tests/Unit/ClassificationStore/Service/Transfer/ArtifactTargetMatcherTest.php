<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Transfer;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyContext;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactAssignmentEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function array_fill;
use function iterator_to_array;

/**
 * The half of an apply that decides nothing: which row an entry describes, whether that row
 * already says what the entry says, and how the store's rows are walked while it is answered.
 *
 * @internal
 */
final class ArtifactTargetMatcherTest extends Unit
{
    private const int CHUNK_SIZE = 200;

    private const string IDENTIFIER = '01GROUP';

    private const string NAME = 'Measurements';

    private InMemoryKeyLocalizationResolver $keyLocalizationResolver;

    public function _before(): void
    {
        $this->keyLocalizationResolver = new InMemoryKeyLocalizationResolver();
    }

    /**
     * The identifier is the identity. A row that carries one is never matched by name, so a file
     * that does not name it leaves it alone instead of overwriting the row that happens to share
     * its label.
     */
    public function testARowWithAnIdentifierIsMatchedByItAlone(): void
    {
        $matcher = $this->createTargetMatcher();
        $entries = [self::IDENTIFIER => ['name' => self::NAME]];
        $byName = $matcher->indexByName($entries, 'name');

        $this->assertSame(
            self::IDENTIFIER,
            $matcher->matchEntryKey($entries, $byName, self::IDENTIFIER, 'Another name')
        );
        $this->assertNull($matcher->matchEntryKey($entries, $byName, '01OTHER', self::NAME));
    }

    /**
     * A row that carries no identifier is the only row a name may match - the row an identifier is
     * adopted onto - and the names are compared the way the database compares them.
     */
    public function testARowWithoutAnIdentifierIsMatchedByNameCaseInsensitively(): void
    {
        $matcher = $this->createTargetMatcher();
        $entries = [self::IDENTIFIER => ['name' => self::NAME]];
        $byName = $matcher->indexByName($entries, 'name');

        $this->assertSame(self::IDENTIFIER, $matcher->matchEntryKey($entries, $byName, null, 'MEASUREMENTS'));
        $this->assertNull($matcher->matchEntryKey($entries, $byName, null, 'Logistics'));
    }

    /**
     * The index answers with one entry per name, and a value that is not a name at all is no entry
     * of it.
     */
    public function testTheNameIndexKeepsTheFirstEntryAndSkipsWhatIsNotAName(): void
    {
        $matcher = $this->createTargetMatcher();

        $byName = $matcher->indexByName(
            [
                'first' => ['name' => self::NAME],
                'second' => ['name' => 'measurements'],
                'third' => ['name' => null],
                'fourth' => [],
            ],
            'name'
        );

        $this->assertSame(['measurements' => 'first'], $byName);
    }

    /**
     * Nothing is walked before the store is known, which is the state a dry run of a file for an
     * installation that does not carry the store yet is in.
     */
    public function testNothingIsWalkedWhileTheStoreIsUnknown(): void
    {
        $calls = 0;
        $walk = static function () use (&$calls): array {
            $calls++;

            return [];
        };

        $walked = iterator_to_array(($this->createTargetMatcher())->walkTargetByIdCursor(
            $this->createContext(null),
            $walk
        ));

        $this->assertSame([], $walked);
        $this->assertSame(0, $calls);
    }

    /**
     * A full chunk means there may be more; the cursor each next chunk is taken after is the id of
     * the last row handed back, so a row an adopted identifier reordered is not walked twice.
     */
    public function testAFullChunkIsFollowedByTheNextOneTakenAfterItsLastId(): void
    {
        $chunks = [];
        $walk = function (int $afterId, int $limit) use (&$chunks): array {
            $chunks[] = $afterId;

            return $afterId === 0 ? $this->createGroups($limit) : [$this->createGroup(9_000)];
        };

        $walked = iterator_to_array(
            ($this->createTargetMatcher())->walkTargetByIdCursor($this->createContext(1), $walk)
        );

        $this->assertCount(self::CHUNK_SIZE + 1, $walked);
        $this->assertSame([0, self::CHUNK_SIZE], $chunks);
    }

    /**
     * A relation has no single row id, so the cursor each next chunk is taken after is the whole
     * last row handed back - never a count of the rows already seen, which a deletion elsewhere
     * would make resume past a row that moved. A chunk that is not full stops the walk.
     */
    public function testTheRelationWalkIsTakenAfterTheLastRowAndStopsOnAShortChunk(): void
    {
        $cursors = [];
        $walk = function (?ScopedKeyAssignment $after, int $limit) use (&$cursors): array {
            $cursors[] = $after?->getKeyIdentifier();

            return $after === null ? $this->createAssignments($limit) : [];
        };

        $walked = iterator_to_array(
            ($this->createTargetMatcher())->walkTargetByRowCursor($this->createContext(1), $walk)
        );

        $this->assertCount(self::CHUNK_SIZE, $walked);
        $this->assertSame([null, '01KEY' . (self::CHUNK_SIZE - 1)], $cursors);
    }

    /**
     * An absent description and an empty one are the same stored value, so a file that writes one
     * where the row carries the other is not a change.
     */
    public function testAnAbsentDescriptionAndAnEmptyOneDescribeTheSameEntity(): void
    {
        $matcher = $this->createTargetMatcher();

        $this->assertTrue($matcher->describesSameEntity(self::NAME, null, self::NAME, ''));
        $this->assertTrue($matcher->describesSameEntity(self::NAME, '', self::NAME, null));
        $this->assertFalse($matcher->describesSameEntity(self::NAME, 'Sizes', self::NAME, null));
        $this->assertFalse($matcher->describesSameEntity('Logistics', null, self::NAME, null));
    }

    /**
     * The key's definition is compared as the structure it holds rather than as the text it is
     * stored as - whitespace in the stored blob is not a change. The comparison is the identity of
     * two decoded arrays, so it is as order sensitive as the export that wrote them: a file whose
     * definition carries the same properties in another order is reported as an update, which
     * re-writes the same value rather than losing one.
     */
    public function testTheKeyDefinitionIsComparedAsAStructure(): void
    {
        $matcher = $this->createTargetMatcher();
        $target = $this->createKey('{ "title": "Weight", "fieldtype": "input" }');

        $this->assertTrue(
            $matcher->describesSameKey($target, $this->createKeyEntry(['title' => 'Weight', 'fieldtype' => 'input']))
        );
        $this->assertFalse($matcher->describesSameKey($target, $this->createKeyEntry(['fieldtype' => 'select'])));
    }

    public function testAKeyWhoseEnabledFlagDiffersIsNotTheSameKey(): void
    {
        $matcher = $this->createTargetMatcher();
        $target = $this->createKey('{"fieldtype":"input"}', enabled: false);

        $this->assertFalse($matcher->describesSameKey($target, $this->createKeyEntry(['fieldtype' => 'input'])));
    }

    /**
     * `localized` is compared as the tri-state it is, so a file whose only change is that flag
     * reports the key as updated - and the three states stay three. Collapsing the stored NULL into
     * `false` would make a key that states nothing look like one flagged not localized, and a file
     * flagging it false would then write nothing.
     *
     * @dataProvider localizedComparisonProvider
     */
    public function testTheLocalizedFlagIsComparedAsATriState(
        ?bool $stored,
        ?bool $entry,
        bool $sameKey
    ): void {
        $target = $this->createKey('{"fieldtype":"input"}', localized: $stored);

        $this->assertSame(
            $sameKey,
            $this->createTargetMatcher()->describesSameKey(
                $target,
                $this->createKeyEntry(['fieldtype' => 'input'], $entry)
            )
        );
    }

    /**
     * @return array<string, array{0: ?bool, 1: ?bool, 2: bool}>
     */
    public static function localizedComparisonProvider(): array
    {
        return [
            'nothing stored and nothing in the file' => [null, null, true],
            'nothing stored against an explicit false' => [null, false, false],
            'nothing stored against an explicit true' => [null, true, false],
            'false stored against nothing in the file' => [false, null, false],
            'false stored against an explicit false' => [false, false, true],
            'true stored against an explicit false' => [true, false, false],
            'true stored against an explicit true' => [true, true, true],
        ];
    }

    /**
     * A per assignment column the entry leaves out is not written, so it is not compared either -
     * which is what keeps a file that says nothing about it from re-writing the row every deploy.
     */
    public function testAnOmittedPerAssignmentColumnIsNotCompared(): void
    {
        $matcher = $this->createTargetMatcher();
        $target = new KeyGroupRelation();
        $target->setSorter(1);
        $target->setMandatory(false);
        $target->setSubGroup('01SUBGROUP');

        $this->assertTrue($matcher->describesSameAssignment($target, $this->createAssignmentEntry()));
        $this->assertFalse(
            $matcher->describesSameAssignment($target, $this->createAssignmentEntry(subGroup: null, supplied: true))
        );
        $this->assertTrue(
            $matcher->describesSameAssignment(
                $target,
                $this->createAssignmentEntry(subGroup: '01SUBGROUP', supplied: true)
            )
        );
    }

    public function testAnAssignmentWhoseSorterDiffersIsNotTheSameAssignment(): void
    {
        $target = new KeyGroupRelation();
        $target->setSorter(2);
        $target->setMandatory(false);

        $this->assertFalse(
            ($this->createTargetMatcher())->describesSameAssignment($target, $this->createAssignmentEntry())
        );
    }

    public function testAValueThatIsNotAStringReadsAsNone(): void
    {
        $matcher = $this->createTargetMatcher();

        $this->assertSame('Sizes', $matcher->readNullableString(['description' => 'Sizes'], 'description'));
        $this->assertNull($matcher->readNullableString(['description' => null], 'description'));
        $this->assertNull($matcher->readNullableString([], 'description'));
    }

    private function createContext(?int $storeId): ApplyContext
    {
        $context = new ApplyContext(true, new ApplyReport());
        $context->setStoreId($storeId);

        return $context;
    }

    /**
     * @return GroupConfig[]
     */
    private function createGroups(int $count): array
    {
        $groups = [];
        foreach (array_fill(0, $count, null) as $index => $nothing) {
            $groups[] = $this->createGroup($index + 1);
        }

        return $groups;
    }

    private function createGroup(int $id): GroupConfig
    {
        $group = new GroupConfig();
        $group->setId($id);
        $group->setName(self::NAME);

        return $group;
    }

    /**
     * @return ScopedKeyAssignment[]
     */
    private function createAssignments(int $count): array
    {
        $assignments = [];
        foreach (array_fill(0, $count, null) as $index => $nothing) {
            $assignments[] = new ScopedKeyAssignment(new KeyGroupRelation(), '01GROUP', '01KEY' . $index);
        }

        return $assignments;
    }

    private function createTargetMatcher(): ArtifactTargetMatcher
    {
        return new ArtifactTargetMatcher($this->keyLocalizationResolver);
    }

    private function createKey(string $definition, bool $enabled = true, ?bool $localized = null): KeyConfig
    {
        $key = new KeyConfig();
        $key->setName('weight');
        $key->setTitle('Weight');
        $key->setType('input');
        $key->setDefinition($definition);
        $key->setEnabled($enabled);
        $this->keyLocalizationResolver->setLocalized($key, $localized);

        return $key;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createKeyEntry(array $definition, ?bool $localized = null): ArtifactKeyEntry
    {
        return new ArtifactKeyEntry('01KEY', 'weight', 'Weight', null, 'input', $definition, true, $localized);
    }

    private function createAssignmentEntry(?string $subGroup = null, bool $supplied = false): ArtifactAssignmentEntry
    {
        return new ArtifactAssignmentEntry(
            '01GROUP',
            '01KEY',
            1,
            false,
            null,
            null,
            $subGroup,
            false,
            false,
            $supplied
        );
    }
}
