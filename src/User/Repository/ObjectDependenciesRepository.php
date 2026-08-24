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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\User as UserFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Listing as ObjectListing;

/**
 * @internal
 */
final readonly class ObjectDependenciesRepository implements ObjectDependenciesRepositoryInterface
{
    public function getObjectsReferencingUser(int $userId, int $offset, int $limit): array
    {
        $items = [];
        $totalItems = 0;
        $matchesBeforeCurrentClass = 0;

        foreach ($this->getClassesWithUserField() as $className => $fields) {
            $list = $this->createListingForClass($className, $fields, $userId);
            $classMatchCount = $list->count();
            $totalItems += $classMatchCount;

            $stillNeeded = $limit - count($items);
            if ($stillNeeded > 0) {
                $window = $this->resolveClassWindow(
                    $matchesBeforeCurrentClass,
                    $classMatchCount,
                    $offset,
                    $limit,
                    $stillNeeded
                );

                if ($window !== null) {
                    [$localOffset, $localLimit] = $window;
                    $list->setOffset($localOffset);
                    $list->setLimit($localLimit);
                    // A per-class Listing (e.g. Issue1106\Listing) only ever loads Concrete
                    // instances of that class, but Listing::load() is typed to the broader
                    // DataObject; filter to narrow it back for callers that expect Concrete.
                    $loaded = array_filter(
                        $list->load(),
                        static fn (DataObject $object): bool => $object instanceof Concrete
                    );
                    $items = array_merge($items, $loaded);
                }
            }

            $matchesBeforeCurrentClass += $classMatchCount;
        }

        return ['items' => $items, 'totalItems' => $totalItems];
    }

    /**
     * Pure windowing math: given where this class's matches sit within the overall
     * (unpaginated, cross-class) sequence, returns the [offset, limit] to apply to
     * this class's own listing to fill the remainder of the requested page - or
     * null if this class's matches don't intersect the requested window at all.
     *
     * @return array{0: int, 1: int}|null
     */
    private function resolveClassWindow(
        int $matchesBeforeCurrentClass,
        int $classMatchCount,
        int $offset,
        int $limit,
        int $stillNeeded
    ): ?array {
        $classStart = $matchesBeforeCurrentClass;
        $classEnd = $classStart + $classMatchCount;
        $windowStart = $offset;
        $windowEnd = $offset + $limit;

        if ($classEnd <= $windowStart || $classStart >= $windowEnd) {
            return null;
        }

        $localOffset = max(0, $windowStart - $classStart);
        $localLimit = min($classMatchCount - $localOffset, $stillNeeded);

        if ($localLimit <= 0) {
            return null;
        }

        return [$localOffset, $localLimit];
    }

    /**
     * @return array<string, string[]> Class name => names of its `User`-type fields
     */
    private function getClassesWithUserField(): array
    {
        $classesList = new ClassDefinitionListing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');

        $classesToCheck = [];
        foreach ($classesList as $class) {
            $userFieldNames = [];
            foreach ($class->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition instanceof UserFieldDefinition) {
                    $userFieldNames[] = $fieldDefinition->getName();
                }
            }

            if ($userFieldNames !== []) {
                $classesToCheck[$class->getName()] = $userFieldNames;
            }
        }

        return $classesToCheck;
    }

    /**
     * @param string[] $userFieldNames
     */
    private function createListingForClass(string $className, array $userFieldNames, int $userId): ObjectListing
    {
        $listingClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';

        /** @var ObjectListing $list */
        $list = new $listingClass();
        [$condition, $params] = $this->buildUserFieldCondition($userFieldNames, $userId);
        $list->setCondition($condition, $params);
        // Deterministic order is required for offset/limit paging to be stable across requests.
        $list->setOrderKey('id');
        $list->setOrder('asc');

        return $list;
    }

    /**
     * OR, not AND: the object references the user if ANY of its User-type fields does,
     * not only if all of them (independently) happen to point at the same user.
     *
     * @param string[] $userFieldNames
     *
     * @return array{0: string, 1: int[]}
     */
    private function buildUserFieldCondition(array $userFieldNames, int $userId): array
    {
        $conditionParts = [];
        foreach ($userFieldNames as $userFieldName) {
            $conditionParts[] = $userFieldName . ' = ?';
        }

        return [implode(' OR ', $conditionParts), array_fill(0, count($conditionParts), $userId)];
    }
}
