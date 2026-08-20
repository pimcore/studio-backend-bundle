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

use Pimcore\Model\DataObject\ClassDefinition\Data\User as UserFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
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
                $classStart = $matchesBeforeCurrentClass;
                $classEnd = $classStart + $classMatchCount;
                $windowStart = $offset;
                $windowEnd = $offset + $limit;

                if ($classEnd > $windowStart && $classStart < $windowEnd) {
                    $localOffset = max(0, $windowStart - $classStart);
                    $localLimit = min($classMatchCount - $localOffset, $stillNeeded);

                    if ($localLimit > 0) {
                        $list->setOffset($localOffset);
                        $list->setLimit($localLimit);
                        $items = array_merge($items, $list->load());
                    }
                }
            }

            $matchesBeforeCurrentClass += $classMatchCount;
        }

        return ['items' => $items, 'totalItems' => $totalItems];
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
        $conditionParts = [];
        foreach ($userFieldNames as $userFieldName) {
            $conditionParts[] = $userFieldName . ' = ?';
        }
        $list->setCondition(implode(' AND ', $conditionParts), array_fill(0, count($conditionParts), $userId));

        return $list;
    }
}
