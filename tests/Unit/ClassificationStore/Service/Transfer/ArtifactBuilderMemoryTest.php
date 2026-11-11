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

use const STDERR;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactBuilder;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactEntryMapper;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedCollectionRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ScopedKeyAssignment;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function count;
use function fwrite;
use function getenv;
use function memory_get_peak_usage;
use function sprintf;
use function str_repeat;
use function strlen;
use function substr;

/**
 * The measurement behind the accumulator's claim: what the export holds is one identifier per
 * entity, not the entries. It streams the ETIM shape - 17,400 keys, 5,640 groups, 159 collections,
 * 18 sub groups, 76,625 assignments - through the fakes and reports `memory_get_peak_usage(true)`.
 *
 * Skipped unless AC9_MEASURE=1. The peak of a process only ever rises, so the two runs are two
 * processes, the second with the entries padded:
 *
 *   AC9_MEASURE=1 AC9_PAD=0     vendor/bin/codecept run Unit tests/Unit/.../ArtifactBuilderMemoryTest.php
 *   AC9_MEASURE=1 AC9_PAD=20000 vendor/bin/codecept run Unit tests/Unit/.../ArtifactBuilderMemoryTest.php
 *
 * @internal
 */
final class ArtifactBuilderMemoryTest extends Unit
{
    private const int CHUNK = 200;

    private const int KEYS = 17400;

    private const int GROUPS = 5640;

    private const int COLLECTIONS = 159;

    private const int SUB_GROUPS = 18;

    private const int ASSIGNMENTS = 76625;

    public function testThePeakDoesNotFollowTheSizeOfTheEntries(): void
    {
        if (getenv('AC9_MEASURE') !== '1') {
            $this->markTestSkipped('A measurement, not a regression test: set AC9_MEASURE=1.');
        }

        $padding = str_repeat('x', (int) getenv('AC9_PAD'));
        $builder = $this->createBuilder($padding);
        $store = new StoreConfig();
        $store->setId(1);
        $store->setIdentifier('STORE');
        $store->setName('ETIM');

        $before = memory_get_peak_usage(true);
        $bytes = 0;
        foreach ($builder->stream($store) as $piece) {
            $bytes += strlen($piece);
        }

        fwrite(STDERR, sprintf(
            "\nAC9 padding=%d document=%d bytes peak before=%d after=%d bytes\n",
            strlen($padding),
            $bytes,
            $before,
            memory_get_peak_usage(true)
        ));
        $this->assertGreaterThan(0, $bytes);
    }

    private function createBuilder(string $padding): ArtifactBuilder
    {
        $subGroups = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroups->method('getChunkAfterIdentifier')->willReturnCallback(
            fn (int $s, ?string $after): array => $this->page(
                self::SUB_GROUPS,
                $after,
                function (int $i) use ($padding): SubGroupConfig {
                    $c = new SubGroupConfig();
                    $c->setId($i + 1);
                    $c->setStoreId(1);
                    $c->setIdentifier($this->id('S', $i));
                    $c->setName('sub' . $padding);

                    return $c;
                }
            )
        );

        $collections = $this->createMock(CollectionRepositoryInterface::class);
        $collections->method('getChunkAfterIdentifier')->willReturnCallback(
            fn (int $s, ?string $after): array => $this->page(
                self::COLLECTIONS,
                $after,
                function (int $i) use ($padding): CollectionConfig {
                    $c = new CollectionConfig();
                    $c->setId($i + 1);
                    $c->setStoreId(1);
                    $c->setIdentifier($this->id('C', $i));
                    $c->setName('collection' . $padding);

                    return $c;
                }
            )
        );

        $groups = $this->createMock(GroupRepositoryInterface::class);
        $groups->method('getChunkAfterIdentifier')->willReturnCallback(
            fn (int $s, ?string $after): array => $this->page(
                self::GROUPS,
                $after,
                function (int $i) use ($padding): GroupConfig {
                    $c = new GroupConfig();
                    $c->setId($i + 1);
                    $c->setStoreId(1);
                    $c->setIdentifier($this->id('G', $i));
                    $c->setName('group' . $padding);

                    return $c;
                }
            )
        );

        $keys = $this->createMock(KeyRepositoryInterface::class);
        $keys->method('getChunkAfterIdentifier')->willReturnCallback(
            fn (int $s, bool $disabledOnly, ?string $after): array => $disabledOnly ? [] : $this->page(
                self::KEYS,
                $after,
                function (int $i) use ($padding): KeyConfig {
                    $c = new KeyConfig();
                    $c->setId($i + 1);
                    $c->setStoreId(1);
                    $c->setIdentifier($this->id('K', $i));
                    $c->setName('key' . $padding);
                    $c->setType('input');
                    $c->setDefinition('{"fieldtype":"input"}');
                    $c->setEnabled(true);
                    $c->setCreationDate(1734567890);

                    return $c;
                }
            )
        );

        $collectionRelations = $this->createMock(CollRelationRepositoryInterface::class);
        $position = 0;
        $collectionRelations->method('getChunkAfter')->willReturnCallback(
            function (int $s, ?ScopedCollectionRelation $after) use (&$position): array {
                $position = $after === null ? 0 : $position;
                $out = [];
                for ($n = 0; $n < self::CHUNK && $position < self::GROUPS; $n++, $position++) {
                    $relation = new CollectionGroupRelation();
                    $relation->setColId(($position % self::COLLECTIONS) + 1);
                    $relation->setGroupId($position + 1);
                    $relation->setSorter($position);
                    $out[] = new ScopedCollectionRelation(
                        $relation,
                        $this->id('C', $position % self::COLLECTIONS),
                        $this->id('G', $position)
                    );
                }

                return $out;
            }
        );

        $assignments = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $at = 0;
        $assignments->method('getChunkAfter')->willReturnCallback(
            function (int $s, ?ScopedKeyAssignment $after) use (&$at): array {
                $at = $after === null ? 0 : $at;
                $out = [];
                for ($n = 0; $n < self::CHUNK && $at < self::ASSIGNMENTS; $n++, $at++) {
                    $relation = new KeyGroupRelation();
                    $relation->setKeyId(($at % self::KEYS) + 1);
                    $relation->setGroupId(($at % self::GROUPS) + 1);
                    $relation->setSorter($at);
                    $relation->setMandatory(false);
                    $relation->setMetadata(null);
                    $relation->setDefinitionOverride(null);
                    $relation->setSubGroup(null);
                    $out[] = new ScopedKeyAssignment(
                        $relation,
                        $this->id('G', $at % self::GROUPS),
                        $this->id('K', $at % self::KEYS)
                    );
                }

                return $out;
            }
        );

        return new ArtifactBuilder(
            new ArtifactEntryMapper(new InMemoryKeyLocalizationResolver()),
            $collections,
            $collectionRelations,
            $groups,
            $assignments,
            $keys,
            $subGroups
        );
    }

    /**
     * The next chunk after the identifier given, for a section of `$total` entities with ascending
     * zero padded identifiers.
     *
     * @param callable(int): object $make
     *
     * @return list<object>
     */
    private function page(int $total, ?string $after, callable $make): array
    {
        $from = $after === null ? 0 : ((int) substr($after, 1)) + 1;
        $out = [];

        for ($i = $from; $i < $total && count($out) < self::CHUNK; $i++) {
            $out[] = $make($i);
        }

        return $out;
    }

    private function id(string $prefix, int $index): string
    {
        return sprintf('%s%025d', $prefix, $index);
    }
}
