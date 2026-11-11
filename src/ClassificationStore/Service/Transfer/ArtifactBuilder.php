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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_map;
use function count;
use function implode;
use function json_encode;
use function sprintf;
use function strcmp;

/**
 * @internal
 */
final readonly class ArtifactBuilder implements ArtifactBuilderInterface
{
    private const int CHUNK_SIZE = 200;

    /**
     * What an entry of each section is called in a failure, in the words the apply side reports it in.
     */
    private const array SECTION_LEVELS = [
        'subGroups' => 'sub group',
        'collections' => 'collection',
        'groups' => 'group',
        'keys' => 'key',
        'collectionGroupRelations' => 'collection group relation',
        'keyGroupRelations' => 'key assignment',
    ];

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
        $written = [];

        yield '{' . $this->encodeMember(
            ArtifactSection::STORE->value,
            $this->encode($this->entryMapper->mapStore($store))
        );

        yield from $this->streamSection(
            ArtifactSection::SUB_GROUPS,
            $this->remember(
                ArtifactSection::SUB_GROUPS,
                $this->walk(
                    fn (?SubGroupConfig $after, int $limit): array => $this->subGroupRepository
                        ->getChunkAfterIdentifier($storeId, $after?->getIdentifier(), (int) $after?->getId(), $limit),
                    $this->entryMapper->mapSubGroup(...)
                ),
                $written
            )
        );

        yield from $this->streamSection(
            ArtifactSection::COLLECTIONS,
            $this->remember(
                ArtifactSection::COLLECTIONS,
                $this->walk(
                    fn (?CollectionConfig $after, int $limit): array => $this->collectionRepository
                        ->getChunkAfterIdentifier($storeId, $after?->getIdentifier(), (int) $after?->getId(), $limit),
                    $this->entryMapper->mapCollection(...)
                ),
                $written
            )
        );

        yield from $this->streamSection(
            ArtifactSection::GROUPS,
            $this->remember(
                ArtifactSection::GROUPS,
                $this->walk(
                    fn (?GroupConfig $after, int $limit): array => $this->groupRepository
                        ->getChunkAfterIdentifier($storeId, $after?->getIdentifier(), (int) $after?->getId(), $limit),
                    $this->entryMapper->mapGroup(...)
                ),
                $written
            )
        );

        yield from $this->streamSection(
            ArtifactSection::KEYS,
            $this->remember(ArtifactSection::KEYS, $this->walkEveryKey($storeId), $written)
        );

        yield from $this->streamSection(
            ArtifactSection::COLLECTION_GROUP_RELATIONS,
            $this->resolve(
                ArtifactSection::COLLECTION_GROUP_RELATIONS,
                $this->walk(
                    fn (?ScopedCollectionRelation $after, int $limit): array => $this->collectionRelationRepository
                        ->getChunkAfter($storeId, $after, $limit),
                    $this->entryMapper->mapCollectionRelation(...)
                ),
                [
                    ArtifactField::COLLECTION->value => ArtifactSection::COLLECTIONS,
                    ArtifactField::GROUP->value => ArtifactSection::GROUPS,
                ],
                $written
            )
        );

        yield from $this->streamSection(
            ArtifactSection::KEY_GROUP_RELATIONS,
            $this->resolve(
                ArtifactSection::KEY_GROUP_RELATIONS,
                $this->walk(
                    fn (?ScopedKeyAssignment $after, int $limit): array => $this->keyGroupRelationRepository
                        ->getChunkAfter($storeId, $after, $limit),
                    $this->entryMapper->mapKeyAssignment(...)
                ),
                [
                    ArtifactField::GROUP->value => ArtifactSection::GROUPS,
                    ArtifactField::KEY->value => ArtifactSection::KEYS,
                    ArtifactField::SUB_GROUP->value => ArtifactSection::SUB_GROUPS,
                ],
                $written
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
     * `strcmp()`, not `<=`: PHP compares two numeric strings numerically whatever their type, so a
     * pair of identifiers that both read as numbers would be merged in numeric order while each
     * walk hands them over in the string order the database sorted them in. An identifier may be
     * digits and `E` - `/^[A-Za-z0-9][A-Za-z0-9_-]*$/` permits it - and `00000000000000000000002E10`
     * sorts before `00000000000000000000003E09` as a string while reading as the larger number, so
     * `<=` would take the second one first and emit a section that is not sorted by identifier.
     *
     * @param Generator<KeyConfig> $first
     * @param Generator<KeyConfig> $second
     *
     * @return Generator<KeyConfig>
     */
    private function mergeByIdentifier(Generator $first, Generator $second): Generator
    {
        while ($first->valid() && $second->valid()) {
            $takeFirst = strcmp(
                (string) $first->current()->getIdentifier(),
                (string) $second->current()->getIdentifier()
            ) <= 0;
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
     * Passes every entry through and notes the identifier it carries, so that a relation section can
     * be checked against what was actually written rather than against a second read.
     *
     * @param iterable<array<string, mixed>> $entries
     * @param array<string, array<string, true>> $written
     *
     * @return Generator<array<string, mixed>>
     */
    private function remember(ArtifactSection $section, iterable $entries, array &$written): Generator
    {
        $written[$section->value] ??= [];

        foreach ($entries as $entry) {
            $identifier = $entry[ArtifactField::IDENTIFIER->value] ?? null;

            if ($identifier !== null) {
                $written[$section->value][(string) $identifier] = true;
            }

            yield $entry;
        }
    }

    /**
     * Passes every relation entry through once each reference it names was written to its section,
     * and refuses at the first one that was not - before the entry is written.
     *
     * @param iterable<array<string, mixed>> $entries
     * @param array<string, ArtifactSection> $references
     * @param array<string, array<string, true>> $written
     *
     * @return Generator<array<string, mixed>>
     *
     * @throws ConflictException
     */
    private function resolve(ArtifactSection $section, iterable $entries, array $references, array &$written): Generator
    {
        foreach ($entries as $entry) {
            foreach ($references as $field => $target) {
                $identifier = $entry[$field] ?? null;

                if ($identifier === null || isset($written[$target->value][(string) $identifier])) {
                    continue;
                }

                throw new ConflictException($this->describeMissingReference($section, $entry, $field, $target));
            }

            yield $entry;
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function describeMissingReference(
        ArtifactSection $section,
        array $entry,
        string $field,
        ArtifactSection $target
    ): string {
        $joined = $section === ArtifactSection::KEY_GROUP_RELATIONS
            ? [ArtifactField::GROUP->value, ArtifactField::KEY->value]
            : [ArtifactField::COLLECTION->value, ArtifactField::GROUP->value];

        return sprintf(
            '%s "%s": it names the %s "%s", which exists neither in the file nor in the store. '
            . 'The store configuration changed while it was being exported; run the export again.',
            self::SECTION_LEVELS[$section->value],
            implode(' -> ', array_map(static fn (string $name): string => (string) $entry[$name], $joined)),
            self::SECTION_LEVELS[$target->value],
            (string) $entry[$field]
        );
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
