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

use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\Service as ClassificationStoreService;
use function array_values;

/**
 * Resolves a store to the search indices it reaches. The index is per data object class while the
 * store is not, so one store answers for as many indices as there are classes carrying a field of
 * it - and for none at all while no class does.
 *
 * The fields of a class are collected by core's own walk, which reaches the ones nested in localized
 * fields, object bricks and field collections as well: a store used only inside a brick is reached
 * by exactly the same keys as one used at the top level, so a remap that only looked at a class's
 * own fields would leave that index behind
 * ({@see ClassificationStoreService::getClassificationStoreFieldDefinitions()} is the walk a class
 * save uses to resolve the very same fields).
 *
 * @internal
 */
final readonly class SearchIndexRemapTargetResolver implements SearchIndexRemapTargetResolverInterface
{
    public function __construct(
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
    ) {
    }

    /**
     * The class id is the value of its own entry as well as its key, because a class id made of
     * digits is an integer array key and the queue is handed the id as the string a class carries
     * it as - reading the keys back would hand it an integer and fail the save it was triggered by.
     *
     * {@inheritdoc}
     */
    public function resolveForStore(int $storeId): array
    {
        $targets = [];

        foreach ($this->classDefinitionRepository->getClassDefinitions() as $class) {
            $classId = $class->getId();

            if ($classId === null || isset($targets[$classId]) || !$this->carriesStore($class, $storeId)) {
                continue;
            }

            $targets[$classId] = $classId;
        }

        return array_values($targets);
    }

    private function carriesStore(ClassDefinition $class, int $storeId): bool
    {
        $fieldDefinitions = ClassificationStoreService::getClassificationStoreFieldDefinitions(
            $class->getFieldDefinitions()
        );

        foreach ($fieldDefinitions as $fieldDefinition) {
            if (ClassificationStoreService::getConfiguredStoreId($fieldDefinition) === $storeId) {
                return true;
            }
        }

        return false;
    }
}
