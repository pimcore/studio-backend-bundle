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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Symfony\Contracts\Service\ResetInterface;
use WeakReference;
use function array_filter;
use function array_map;
use function count;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;
use function str_starts_with;

/**
 * The identifiers a request reads are memoised by {@see prime()}, and dropped by {@see reset()} so that a
 * long-running worker never serves the ids it saw first.
 *
 * @internal
 */
final class ColumnReferenceService implements ColumnReferenceServiceInterface, ResetInterface
{
    private const string ADVANCED_TYPE = 'dataobject.advanced';

    private const string CLASSIFICATION_STORE_TYPE = 'dataobject.classificationstore';

    private const string FILTER_TYPE_PREFIX = 'classificationstore.';

    private const string GROUP_IDENTIFIER = 'groupIdentifier';

    private const string KEY_IDENTIFIER = 'keyIdentifier';

    private const string GROUP_ID = 'groupId';

    private const string KEY_ID = 'keyId';

    /**
     * @var array<int, array<string, int>> group id by identifier, by store id
     */
    private array $groupIds = [];

    /**
     * @var array<int, array<string, int>> key id by identifier, by store id
     */
    private array $keyIds = [];

    private ?WeakReference $primedFor = null;

    private ?string $primedClassId = null;

    public function __construct(
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly KeyRepositoryInterface $keyRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $classId, string $fieldName, array $config): ?ClassificationStoreConfig
    {
        if (!$this->hasIdentifiers($config)) {
            return null;
        }

        $storeId = $this->getStoreId($classId, $fieldName);
        $groupIdentifier = $config[self::GROUP_IDENTIFIER];
        $keyIdentifier = $config[self::KEY_IDENTIFIER];

        $groupId = $this->groupIds[$storeId][$groupIdentifier]
            ?? $this->groupRepository->getIdsByIdentifiers($storeId, [$groupIdentifier])[$groupIdentifier]
            ?? null;

        if ($groupId === null) {
            throw new InvalidArgumentException(
                sprintf('Classification store group "%s" does not exist in this installation', $groupIdentifier)
            );
        }

        $keyId = $this->keyIds[$storeId][$keyIdentifier]
            ?? $this->keyRepository->getIdsByIdentifiers($storeId, [$keyIdentifier])[$keyIdentifier]
            ?? null;

        if ($keyId === null) {
            throw new InvalidArgumentException(
                sprintf('Classification store key "%s" does not exist in this installation', $keyIdentifier)
            );
        }

        return new ClassificationStoreConfig($groupId, $keyId);
    }

    /**
     * {@inheritdoc}
     */
    public function prime(string $classId, ColumnCollection $columns): void
    {
        if ($this->primedClassId === $classId && $this->primedFor?->get() === $columns) {
            return;
        }

        $this->reset();

        $columnArrays = array_map(static fn ($column) => $column->toArray(), $columns->getColumns());
        $withIdentifiers = array_filter(
            $this->references($columnArrays),
            fn (array $reference) => $this->hasIdentifiers($this->configAt($columnArrays, $reference))
        );
        [$this->groupIds, $this->keyIds] = $this->lookUpIds($classId, $columnArrays, $withIdentifiers);

        $this->primedClassId = $classId;
        $this->primedFor = WeakReference::create($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function addIdentifiers(string $classId, array $columns): array
    {
        $columns = $this->deriveIds($classId, $columns);
        $references = array_filter(
            $this->references($columns),
            fn (array $reference) => $this->hasIdPair($this->configAt($columns, $reference))
                && !$this->hasIdentifiers($this->configAt($columns, $reference))
        );

        $groupIdentifiers = [];
        $keyIdentifiers = [];
        foreach ($this->groupByStore($classId, $columns, $references) as $storeId => $storeReferences) {
            $configs = array_map(fn (array $reference) => $this->configAt($columns, $reference), $storeReferences);
            $groupIdentifiers[$storeId] = $this->groupRepository->getIdentifiersByIds(
                $storeId,
                array_map(static fn (array $config) => $config[self::GROUP_ID], $configs)
            );
            $keyIdentifiers[$storeId] = $this->keyRepository->getIdentifiersByIds(
                $storeId,
                array_map(static fn (array $config) => $config[self::KEY_ID], $configs)
            );
        }

        foreach ($references as $reference) {
            $storeId = $this->storeIdOf($classId, $reference);
            if ($storeId === null) {
                continue;
            }

            $config = $this->configAt($columns, $reference);
            unset($config[self::GROUP_IDENTIFIER], $config[self::KEY_IDENTIFIER]);

            $groupIdentifier = $groupIdentifiers[$storeId][$config[self::GROUP_ID]] ?? null;
            $keyIdentifier = $keyIdentifiers[$storeId][$config[self::KEY_ID]] ?? null;
            if ($groupIdentifier !== null) {
                $config[self::GROUP_IDENTIFIER] = $groupIdentifier;
            }
            if ($keyIdentifier !== null) {
                $config[self::KEY_IDENTIFIER] = $keyIdentifier;
            }

            $columns = $this->replaceConfig($columns, $reference, $config);
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function deriveIds(string $classId, array $columns): array
    {
        $references = array_filter(
            $this->references($columns),
            fn (array $reference) => $this->hasIdentifiers($this->configAt($columns, $reference))
        );

        [$groupIds, $keyIds] = $this->lookUpIds($classId, $columns, $references);

        foreach ($references as $reference) {
            $storeId = $this->storeIdOf($classId, $reference);
            if ($storeId === null) {
                continue;
            }

            $config = $this->configAt($columns, $reference);
            $groupId = $groupIds[$storeId][$config[self::GROUP_IDENTIFIER]] ?? null;
            $keyId = $keyIds[$storeId][$config[self::KEY_IDENTIFIER]] ?? null;

            if ($groupId === null || $keyId === null) {
                continue;
            }

            $config[self::GROUP_ID] = $groupId;
            $config[self::KEY_ID] = $keyId;
            $columns = $this->replaceConfig($columns, $reference, $config);
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilterIdentifiers(string $classId, array $filter): array
    {
        return $this->translateFilter($filter, fn (array $columns) => $this->addIdentifiers($classId, $columns));
    }

    /**
     * {@inheritdoc}
     */
    public function deriveFilterIds(string $classId, array $filter): array
    {
        return $this->translateFilter($filter, fn (array $columns) => $this->deriveIds($classId, $columns));
    }

    public function reset(): void
    {
        $this->groupIds = [];
        $this->keyIds = [];
        $this->primedFor = null;
        $this->primedClassId = null;
    }

    /**
     * The classification store column filters are translated as the columns they filter, then written back.
     *
     * @param callable(array<int, array>): array<int, array> $translate
     */
    private function translateFilter(array $filter, callable $translate): array
    {
        $columns = [];
        foreach (is_array($filter['columnFilters'] ?? null) ? $filter['columnFilters'] : [] as $index => $columnFilter) {
            if (
                is_array($columnFilter)
                && is_string($columnFilter['type'] ?? null)
                && str_starts_with($columnFilter['type'], self::FILTER_TYPE_PREFIX)
                && is_string($columnFilter['key'] ?? null)
                && is_array($columnFilter['filterValue'] ?? null)
            ) {
                $columns[$index] = [
                    'type' => self::CLASSIFICATION_STORE_TYPE,
                    'key' => $columnFilter['key'],
                    'config' => $columnFilter['filterValue'],
                ];
            }
        }

        foreach ($translate($columns) as $index => $column) {
            $filter['columnFilters'][$index]['filterValue'] = $column['config'];
        }

        return $filter;
    }

    /**
     * @param array<int, array> $columns
     * @param array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}> $references
     *
     * @return array{0: array<int, array<string, int>>, 1: array<int, array<string, int>>}
     */
    private function lookUpIds(string $classId, array $columns, array $references): array
    {
        $groupIds = [];
        $keyIds = [];

        foreach ($this->groupByStore($classId, $columns, $references) as $storeId => $storeReferences) {
            $configs = array_map(fn (array $reference) => $this->configAt($columns, $reference), $storeReferences);
            $groupIds[$storeId] = $this->groupRepository->getIdsByIdentifiers(
                $storeId,
                array_map(static fn (array $config) => $config[self::GROUP_IDENTIFIER], $configs)
            );
            $keyIds[$storeId] = $this->keyRepository->getIdsByIdentifiers(
                $storeId,
                array_map(static fn (array $config) => $config[self::KEY_IDENTIFIER], $configs)
            );
        }

        return [$groupIds, $keyIds];
    }

    /**
     * References whose field is no classification store field of the class are left out.
     *
     * @return array<int, array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}>> references by store id
     */
    private function groupByStore(string $classId, array $columns, array $references): array
    {
        $byStore = [];
        foreach ($references as $reference) {
            $storeId = $this->storeIdOf($classId, $reference);
            if ($storeId !== null) {
                $byStore[$storeId][] = $reference;
            }
        }

        return $byStore;
    }

    private function storeIdOf(string $classId, array $reference): ?int
    {
        try {
            return $this->getStoreId($classId, $reference[2], $reference[3] ?? null);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getStoreId(string $classId, string $fieldName, ?string $relation = null): int
    {
        $classDefinition = $this->classDefinitionResolver->getById($classId);
        if ($relation !== null) {
            $classDefinition = $this->getRelatedClassDefinition($classDefinition, $relation);
        }

        $fieldDefinition = $classDefinition?->getFieldDefinition($fieldName);

        if (!$fieldDefinition instanceof Classificationstore) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a classification store field of class "%s"', $fieldName, $classId)
            );
        }

        return $fieldDefinition->getStoreId();
    }

    /**
     * The class a relation field points at, when it allows exactly one.
     */
    private function getRelatedClassDefinition(?ClassDefinition $classDefinition, string $relation): ?ClassDefinition
    {
        $relationDefinition = $classDefinition?->getFieldDefinition($relation);
        $classes = is_object($relationDefinition) && method_exists($relationDefinition, 'getClasses')
            ? $relationDefinition->getClasses()
            : [];

        if (!is_array($classes) || count($classes) !== 1 || !is_string($classes[0]['classes'] ?? null)) {
            return null;
        }

        return $this->classDefinitionResolver->getByName($classes[0]['classes']);
    }

    /**
     * A reference is a plain classification store column or the source field of an advanced column:
     * [column index, advanced column index or null, name of the classification store field, relation or null].
     *
     * @return array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}>
     */
    private function references(array $columns): array
    {
        $references = [];
        foreach ($columns as $index => $column) {
            $type = $column['type'] ?? null;
            if ($type === self::CLASSIFICATION_STORE_TYPE && is_string($column['key'] ?? null)) {
                $references[] = [$index, null, $column['key']];
            }

            if ($type === self::ADVANCED_TYPE) {
                foreach ($this->advancedReferences($index, $column) as $reference) {
                    $references[] = $reference;
                }
            }
        }

        return $references;
    }

    /**
     * The source fields of an advanced column; a relation field names its field on the related class.
     *
     * @return array<int, array{0: int, 1: int, 2: string, 3: string|null}>
     */
    private function advancedReferences(int $index, array $column): array
    {
        $references = [];
        $advancedColumns = $column['config']['advancedColumns'] ?? [];

        foreach (is_array($advancedColumns) ? $advancedColumns : [] as $advancedIndex => $advancedColumn) {
            $field = $advancedColumn['config']['field'] ?? null;
            $relation = $advancedColumn['config']['relation'] ?? null;
            if (!is_string($field)) {
                continue;
            }

            if (($advancedColumn['key'] ?? null) === 'simpleField') {
                $references[] = [$index, $advancedIndex, $field, null];
            }

            if (($advancedColumn['key'] ?? null) === 'relationField' && is_string($relation)) {
                $references[] = [$index, $advancedIndex, $field, $relation];
            }
        }

        return $references;
    }

    private function configAt(array $columns, array $reference): array
    {
        [$index, $advancedIndex] = $reference;
        $config = $advancedIndex === null
            ? $columns[$index]['config'] ?? []
            : $columns[$index]['config']['advancedColumns'][$advancedIndex]['config'] ?? [];

        return is_array($config) ? $config : [];
    }

    private function replaceConfig(array $columns, array $reference, array $config): array
    {
        [$index, $advancedIndex] = $reference;

        if ($advancedIndex === null) {
            $columns[$index]['config'] = $config;

            return $columns;
        }

        $columns[$index]['config']['advancedColumns'][$advancedIndex]['config'] = $config;

        return $columns;
    }

    private function hasIdentifiers(array $config): bool
    {
        return $this->isFilled($config[self::GROUP_IDENTIFIER] ?? null)
            && $this->isFilled($config[self::KEY_IDENTIFIER] ?? null);
    }

    private function hasIdPair(array $config): bool
    {
        return is_int($config[self::GROUP_ID] ?? null) && is_int($config[self::KEY_ID] ?? null);
    }

    private function isFilled(mixed $identifier): bool
    {
        return is_string($identifier) && $identifier !== '';
    }
}
