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

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use Closure;
use Generator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function count;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
final readonly class ArtifactBuilder implements ArtifactBuilderInterface
{
    private const int CHUNK_SIZE = 200;

    public function __construct(
        private ArtifactEntryMapperInterface $entryMapper,
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
    public function stream(StoreConfig $store): iterable
    {
        $storeId = (int) $store->getId();

        yield '{' . $this->encodeMember(
            ArtifactSection::STORE->value,
            $this->encode($this->entryMapper->mapStore($store))
        );

        yield from $this->streamSection(
            ArtifactSection::SUB_GROUPS,
            $this->walk(
                fn (?SubGroupConfig $after, int $limit): array => $this->subGroupRepository
                    ->getChunkAfterIdentifier($storeId, $after?->getIdentifier(), (int) $after?->getId(), $limit),
                $this->entryMapper->mapSubGroup(...)
            )
        );

        yield from $this->streamSection(
            ArtifactSection::COLLECTIONS,
            $this->walk(
                fn (?CollectionConfig $after, int $limit): array => $this->collectionRepository
                    ->getChunkAfterIdentifier($storeId, $after?->getIdentifier(), (int) $after?->getId(), $limit),
                $this->entryMapper->mapCollection(...)
            )
        );

        yield from $this->streamSection(
            ArtifactSection::GROUPS,
            $this->walk(
                fn (?GroupConfig $after, int $limit): array => $this->groupRepository
                    ->getChunkAfterIdentifier($storeId, $after?->getIdentifier(), (int) $after?->getId(), $limit),
                $this->entryMapper->mapGroup(...)
            )
        );

        yield from $this->streamSection(ArtifactSection::KEYS, $this->walkEveryKey($storeId));

        yield from $this->streamSection(
            ArtifactSection::COLLECTION_GROUP_RELATIONS,
            $this->walk(
                fn (?ScopedCollectionRelation $after, int $limit): array => $this->collectionRelationRepository
                    ->getChunkAfter($storeId, $after, $limit),
                $this->entryMapper->mapCollectionRelation(...)
            )
        );

        yield from $this->streamSection(
            ArtifactSection::KEY_GROUP_RELATIONS,
            $this->walk(
                fn (?ScopedKeyAssignment $after, int $limit): array => $this->keyGroupRelationRepository
                    ->getChunkAfter($storeId, $after, $limit),
                $this->entryMapper->mapKeyAssignment(...)
            )
        );

        yield '}';
    }

    /**
     * Every key of the store, enabled and disabled. The listing's own flag narrows to one of the
     * two states rather than covering both, so the section is the two walks merged back into one
     * ascending run of identifiers.
     *
     * @return Generator<array<string, mixed>>
     */
    private function walkEveryKey(int $storeId): Generator
    {
        $enabled = $this->walkChunks(
            fn (?KeyConfig $after, int $limit): array => $this->keyRepository
                ->getChunkAfterIdentifier($storeId, false, $after?->getIdentifier(), (int) $after?->getId(), $limit)
        );
        $disabled = $this->walkChunks(
            fn (?KeyConfig $after, int $limit): array => $this->keyRepository
                ->getChunkAfterIdentifier($storeId, true, $after?->getIdentifier(), (int) $after?->getId(), $limit)
        );

        foreach ($this->mergeByIdentifier($enabled, $disabled) as $key) {
            yield $this->entryMapper->mapKey($key);
        }
    }

    /**
     * @param Generator<KeyConfig> $first
     * @param Generator<KeyConfig> $second
     *
     * @return Generator<KeyConfig>
     */
    private function mergeByIdentifier(Generator $first, Generator $second): Generator
    {
        while ($first->valid() && $second->valid()) {
            $takeFirst = (string) $first->current()->getIdentifier() <= (string) $second->current()->getIdentifier();
            $taken = $takeFirst ? $first : $second;

            yield $taken->current();
            $taken->next();
        }

        foreach ([$first, $second] as $rest) {
            while ($rest->valid()) {
                yield $rest->current();
                $rest->next();
            }
        }
    }

    /**
     * @param Closure(mixed, int): array<int, mixed> $chunk
     * @param Closure(mixed): array<string, mixed> $map
     *
     * @return Generator<array<string, mixed>>
     */
    private function walk(Closure $chunk, Closure $map): Generator
    {
        foreach ($this->walkChunks($chunk) as $item) {
            yield $map($item);
        }
    }

    /**
     * One section's rows, a chunk at a time, taken after the last row handed back rather than at an
     * offset into the ordering.
     *
     * An export is not one read: it is a read per page per section, and a configuration write may
     * commit between any two of them. Deleting a row the walk has already passed shifts every later
     * row one place towards the start, and an offset resumes past the one that moved - which leaves
     * it out of a download that reports success. Naming the last row seen instead of counting the
     * rows seen is what keeps the walk on the row that did not move.
     *
     * @param Closure(mixed, int): array<int, mixed> $chunk
     *
     * @return Generator<mixed>
     */
    private function walkChunks(Closure $chunk): Generator
    {
        $after = null;

        do {
            $items = $chunk($after, self::CHUNK_SIZE);

            foreach ($items as $item) {
                $after = $item;

                yield $item;
            }
        } while (count($items) === self::CHUNK_SIZE);
    }

    /**
     * @param iterable<array<string, mixed>> $entries
     *
     * @return Generator<string>
     */
    private function streamSection(ArtifactSection $section, iterable $entries): Generator
    {
        yield ',' . $this->encodeMember($section->value, '[');

        $separator = '';
        foreach ($entries as $entry) {
            yield $separator . $this->encode($entry);
            $separator = ',';
        }

        yield ']';
    }

    private function encodeMember(string $name, string $value): string
    {
        return sprintf('"%s":%s', $name, $value);
    }

    /**
     * @param array<string, mixed> $value
     */
    private function encode(array $value): string
    {
        return (string) json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
