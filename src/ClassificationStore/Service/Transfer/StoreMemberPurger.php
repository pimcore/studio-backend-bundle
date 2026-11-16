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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Closure;
use Generator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function count;

/**
 * @internal
 */
final readonly class StoreMemberPurger implements StoreMemberPurgerInterface
{
    private const int CHUNK_SIZE = 200;

    /**
     * The entity levels, in the order the report names them.
     */
    private const array ENTITY_SECTIONS = [
        ArtifactSection::SUB_GROUPS,
        ArtifactSection::COLLECTIONS,
        ArtifactSection::GROUPS,
        ArtifactSection::KEYS,
    ];

    public function __construct(
        private ApplyDiagnosticsInterface $diagnostics,
        private CollectionRepositoryInterface $collectionRepository,
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private GroupRepositoryInterface $groupRepository,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyRepositoryInterface $keyRepository,
        private SubGroupRepositoryInterface $subGroupRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function purge(ApplyContext $context): void
    {
        $storeId = $context->getStoreId();

        if ($storeId === null) {
            return;
        }

        // the whole report is taken before anything is deleted, so a relation is still there to name
        $this->reportEntities($context, $storeId);
        $this->reportRelations($context, $storeId);

        if ($context->isDryRun()) {
            return;
        }

        $this->removeEntities($storeId);
    }

    private function reportEntities(ApplyContext $context, int $storeId): void
    {
        foreach (self::ENTITY_SECTIONS as $section) {
            foreach ($this->walkEntities($section, $storeId) as $row) {
                $context->record(
                    ApplyOutcome::REMOVED,
                    $this->diagnostics->label($section, (string) $row->getName())
                );
            }
        }
    }

    private function reportRelations(ApplyContext $context, int $storeId): void
    {
        $collections = ArtifactSection::COLLECTION_GROUP_RELATIONS;
        $walk = fn (?ScopedCollectionRelation $after, int $limit): array => $this->collectionRelationRepository
            ->getChunkAfter($storeId, $after, $limit);

        foreach ($this->walkByRowCursor($walk) as $scoped) {
            $context->record(
                ApplyOutcome::REMOVED,
                $this->diagnostics->relationLabel(
                    $collections,
                    $scoped->getCollectionIdentifier()
                    . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                    . $scoped->getGroupIdentifier()
                )
            );
        }

        $assignments = ArtifactSection::KEY_GROUP_RELATIONS;
        $walk = fn (?ScopedKeyAssignment $after, int $limit): array => $this->keyGroupRelationRepository
            ->getChunkAfter($storeId, $after, $limit);

        foreach ($this->walkByRowCursor($walk) as $scoped) {
            $context->record(
                ApplyOutcome::REMOVED,
                $this->diagnostics->relationLabel(
                    $assignments,
                    $scoped->getGroupIdentifier()
                    . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
                    . $scoped->getKeyIdentifier()
                )
            );
        }
    }

    /**
     * Keys and groups first: both relation tables hang off them and the database takes the rows of
     * those with them. Sub groups last, because their delete is refused while an assignment still
     * tags one ({@see SubGroupRepositoryInterface::delete()}), and by then there is none.
     */
    private function removeEntities(int $storeId): void
    {
        foreach ($this->walkEntities(ArtifactSection::KEYS, $storeId) as $key) {
            $this->keyRepository->delete((int) $key->getId());
        }

        foreach ($this->walkEntities(ArtifactSection::GROUPS, $storeId) as $group) {
            $this->groupRepository->delete((int) $group->getId());
        }

        foreach ($this->walkEntities(ArtifactSection::COLLECTIONS, $storeId) as $collection) {
            $this->collectionRepository->delete((int) $collection->getId());
        }

        foreach ($this->walkEntities(ArtifactSection::SUB_GROUPS, $storeId) as $subGroup) {
            $this->subGroupRepository->delete((int) $subGroup->getId());
        }
    }

    /**
     * Every row of one entity level, a chunk at a time.
     *
     * @return Generator<SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig>
     */
    private function walkEntities(ArtifactSection $section, int $storeId): Generator
    {
        foreach ($this->chunkReaders($section, $storeId) as $read) {
            yield from $this->walkByIdCursor($read);
        }
    }

    /**
     * The reads one entity level is covered by. Keys take two: the listing's own flag narrows to
     * one of the two enabled states rather than covering both.
     *
     * @return list<Closure(int, int): array<int, SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig>>
     */
    private function chunkReaders(ArtifactSection $section, int $storeId): array
    {
        return match ($section) {
            ArtifactSection::SUB_GROUPS => [
                fn (int $afterId, int $limit): array => $this->subGroupRepository
                    ->getChunkAfterId($storeId, $afterId, $limit),
            ],
            ArtifactSection::COLLECTIONS => [
                fn (int $afterId, int $limit): array => $this->collectionRepository
                    ->getChunkAfterId($storeId, $afterId, $limit),
            ],
            ArtifactSection::GROUPS => [
                fn (int $afterId, int $limit): array => $this->groupRepository
                    ->getChunkAfterId($storeId, $afterId, $limit),
            ],
            default => [
                fn (int $afterId, int $limit): array => $this->keyRepository
                    ->getChunkAfterId($storeId, false, $afterId, $limit),
                fn (int $afterId, int $limit): array => $this->keyRepository
                    ->getChunkAfterId($storeId, true, $afterId, $limit),
            ],
        };
    }

    /**
     * @param Closure(int, int): array<int, SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig> $read
     *
     * @return Generator<SubGroupConfig|CollectionConfig|GroupConfig|KeyConfig>
     */
    private function walkByIdCursor(Closure $read): Generator
    {
        $afterId = 0;

        do {
            $rows = $read($afterId, self::CHUNK_SIZE);

            foreach ($rows as $row) {
                $afterId = (int) $row->getId();

                yield $row;
            }
        } while (count($rows) === self::CHUNK_SIZE);
    }

    /**
     * @template TRow
     *
     * @param Closure(TRow|null, int): array<int, TRow> $read
     *
     * @return Generator<TRow>
     */
    private function walkByRowCursor(Closure $read): Generator
    {
        $after = null;

        do {
            $rows = $read($after, self::CHUNK_SIZE);

            foreach ($rows as $row) {
                $after = $row;

                yield $row;
            }
        } while (count($rows) === self::CHUNK_SIZE);
    }
}
