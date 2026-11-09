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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Symfony\Contracts\Service\ResetInterface;
use WeakReference;
use function array_diff;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * The identifiers a request reads are memoised by {@see prime()}, {@see primeFilters()} and every lookup a
 * {@see resolve()} has to make itself. The memo is keyed by store, which no change of class or of column
 * collection invalidates, so a re-{@see prime()} keeps it and only the request/message boundary drops it:
 * implementing ResetInterface makes autoconfiguration tag the service `kernel.reset`, so {@see reset()}
 * runs there and a long-running worker never serves the ids it saw first.
 *
 * @internal
 */
final class ColumnReferenceService implements ColumnReferenceServiceInterface, ResetInterface
{
    private const string GROUP_IDENTIFIER = ColumnReferenceLocatorInterface::GROUP_IDENTIFIER;

    private const string KEY_IDENTIFIER = ColumnReferenceLocatorInterface::KEY_IDENTIFIER;

    private const string GROUP_ID = ColumnReferenceLocatorInterface::GROUP_ID;

    private const string KEY_ID = ColumnReferenceLocatorInterface::KEY_ID;

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
        private readonly ColumnReferenceLocatorInterface $locator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $classId, string $fieldName, array $config): ?ClassificationStoreConfig
    {
        if (!$this->locator->hasIdentifiers($config)) {
            return null;
        }

        $storeId = $this->getStoreId($classId, $fieldName);
        $groupIdentifier = $config[self::GROUP_IDENTIFIER];
        $keyIdentifier = $config[self::KEY_IDENTIFIER];

        [$groupIds, $keyIds] = $this->memoise($storeId, [$groupIdentifier], [$keyIdentifier]);

        $groupId = $groupIds[$groupIdentifier] ?? null;
        if ($groupId === null) {
            throw new InvalidArgumentException(
                sprintf('Classification store group "%s" does not exist in this installation', $groupIdentifier)
            );
        }

        $keyId = $keyIds[$keyIdentifier] ?? null;
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

        $columnArrays = array_map(static fn ($column) => $column->toArray(), $columns->getColumns());
        $this->lookUpIds($classId, $columnArrays, $this->locator->identifierReferences($columnArrays));

        $this->primedClassId = $classId;
        $this->primedFor = WeakReference::create($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function primeFilters(string $classId, array $columnFilters): void
    {
        $columns = $this->locator->filterColumns($columnFilters);
        $this->lookUpIds($classId, $columns, $this->locator->identifierReferences($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addIdentifiers(string $classId, array $columns): array
    {
        $columns = $this->deriveIds($classId, $columns);
        $references = $this->locator->pairReferences($columns);

        $groupIdentifiers = [];
        $keyIdentifiers = [];
        foreach ($this->groupByStore($classId, $references) as $storeId => $storeReferences) {
            $configs = array_map(
                fn (array $reference) => $this->locator->configAt($columns, $reference),
                $storeReferences
            );
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

            $config = $this->locator->configAt($columns, $reference);
            unset($config[self::GROUP_IDENTIFIER], $config[self::KEY_IDENTIFIER]);

            $groupIdentifier = $groupIdentifiers[$storeId][$config[self::GROUP_ID]] ?? null;
            $keyIdentifier = $keyIdentifiers[$storeId][$config[self::KEY_ID]] ?? null;
            if ($groupIdentifier !== null) {
                $config[self::GROUP_IDENTIFIER] = $groupIdentifier;
            }
            if ($keyIdentifier !== null) {
                $config[self::KEY_IDENTIFIER] = $keyIdentifier;
            }

            $columns = $this->locator->replaceConfig($columns, $reference, $config);
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function deriveIds(string $classId, array $columns): array
    {
        $references = $this->locator->identifierReferences($columns);

        [$groupIds, $keyIds] = $this->lookUpIds($classId, $columns, $references);

        foreach ($references as $reference) {
            $storeId = $this->storeIdOf($classId, $reference);
            if ($storeId === null) {
                continue;
            }

            $config = $this->locator->configAt($columns, $reference);
            $groupId = $groupIds[$storeId][$config[self::GROUP_IDENTIFIER]] ?? null;
            $keyId = $keyIds[$storeId][$config[self::KEY_IDENTIFIER]] ?? null;

            if ($groupId === null || $keyId === null) {
                continue;
            }

            $config[self::GROUP_ID] = $groupId;
            $config[self::KEY_ID] = $keyId;
            $columns = $this->locator->replaceConfig($columns, $reference, $config);
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
        $columnFilters = is_array($filter['columnFilters'] ?? null) ? $filter['columnFilters'] : [];

        foreach ($translate($this->locator->filterColumns($columnFilters)) as $index => $column) {
            $filter['columnFilters'][$index]['filterValue'] = $column['config'];
        }

        return $filter;
    }

    /**
     * The ids of the identifiers the references carry, by store, memoised so that a reference a request
     * reads twice is looked up once.
     *
     * @param array<int, array> $columns
     * @param array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}> $references
     *
     * @return array{0: array<int, array<string, int>>, 1: array<int, array<string, int>>}
     */
    private function lookUpIds(string $classId, array $columns, array $references): array
    {
        $groupIds = [];
        $keyIds = [];

        foreach ($this->groupByStore($classId, $references) as $storeId => $storeReferences) {
            $configs = array_map(
                fn (array $reference) => $this->locator->configAt($columns, $reference),
                $storeReferences
            );
            [$groupIds[$storeId], $keyIds[$storeId]] = $this->memoise(
                $storeId,
                array_map(static fn (array $config) => $config[self::GROUP_IDENTIFIER], $configs),
                array_map(static fn (array $config) => $config[self::KEY_IDENTIFIER], $configs)
            );
        }

        return [$groupIds, $keyIds];
    }

    /**
     * The store's memo, filled with one statement per entity type for the identifiers it does not hold yet.
     * An identifier that resolves nowhere stays absent and is read again, so that it costs no memory per miss.
     *
     * @param array<int, string> $groupIdentifiers
     * @param array<int, string> $keyIdentifiers
     *
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function memoise(int $storeId, array $groupIdentifiers, array $keyIdentifiers): array
    {
        $missingGroups = $this->notMemoised($this->groupIds[$storeId] ?? [], $groupIdentifiers);
        if ($missingGroups !== []) {
            $this->groupIds[$storeId] = ($this->groupIds[$storeId] ?? [])
                + $this->groupRepository->getIdsByIdentifiers($storeId, $missingGroups);
        }

        $missingKeys = $this->notMemoised($this->keyIds[$storeId] ?? [], $keyIdentifiers);
        if ($missingKeys !== []) {
            $this->keyIds[$storeId] = ($this->keyIds[$storeId] ?? [])
                + $this->keyRepository->getIdsByIdentifiers($storeId, $missingKeys);
        }

        return [$this->groupIds[$storeId] ?? [], $this->keyIds[$storeId] ?? []];
    }

    /**
     * @param array<string, int> $memo
     * @param array<int, string> $identifiers
     *
     * @return array<int, string>
     */
    private function notMemoised(array $memo, array $identifiers): array
    {
        return array_values(array_diff(array_unique($identifiers), array_keys($memo)));
    }

    /**
     * References whose field is no classification store field of the class are left out.
     *
     * @param array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}> $references
     *
     * @return array<int, array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}>> references by store id
     */
    private function groupByStore(string $classId, array $references): array
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
     * The class a relation field points at, when it allows exactly one. The `folder` pseudo-class is left out
     * of that count: it names no class definition, and the reader this derivation serves keeps only the
     * Concrete relation targets (Grid\Column\Resolver\DataObject\AdvancedColumnResolver), so a relation
     * allowing a folder beside one object class still has a single source class - which is how
     * Grid\Service\ColumnConfigurationForRelationService reads such a list as well.
     */
    private function getRelatedClassDefinition(?ClassDefinition $classDefinition, string $relation): ?ClassDefinition
    {
        $relationDefinition = $classDefinition?->getFieldDefinition($relation);
        $classes = is_object($relationDefinition) && method_exists($relationDefinition, 'getClasses')
            ? $relationDefinition->getClasses()
            : [];

        $classNames = is_array($classes) ? $this->objectClassNames($classes) : [];

        if (count($classNames) !== 1) {
            return null;
        }

        return $this->classDefinitionResolver->getByName($classNames[0]);
    }

    /**
     * The object class names of an allowed-class list, without the `folder` pseudo-class.
     *
     * @param array<int, mixed> $classes
     *
     * @return array<int, string>
     */
    private function objectClassNames(array $classes): array
    {
        $names = [];
        foreach ($classes as $class) {
            $name = is_array($class) ? ($class['classes'] ?? null) : null;
            if (is_string($name) && $name !== ElementTypes::TYPE_FOLDER) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
