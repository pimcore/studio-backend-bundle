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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\Tree\FolderNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\Tree\NodeHydratorInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use function count;

/**
 * @internal
 */
final readonly class ClassDefinitionTreeService implements ClassDefinitionTreeServiceInterface
{
    public function __construct(
        private ClassDefinitionServiceInterface $classDefinitionService,
        private FolderNodeHydratorInterface $folderNodeHydrator,
        private NodeHydratorInterface $nodeHydrator
    ) {
    }

    public function getTree(bool $grouped = false): array
    {
        $cds = $this->classDefinitionService->getClassDefinitions();
        $groups = $this->getGroups($cds);

        if (empty($groups)) {
            return [];
        }

        $groups = $this->sortGroups($groups);
        if ($grouped === true) {
            return $this->getGroupedNodes($groups);
        }

        $hydrated = [];
        foreach ($groups as $groupData) {
            foreach ($groupData['classes'] as $class) {
                $node = $this->nodeHydrator->hydrate($class);
                $hydrated[] = $node;
            }
        }

        return $hydrated;
    }

    /**
     * @param ClassDefinition[] $classDefinitions
     */
    private function getGroups(array $classDefinitions): array
    {
        $groups = [];

        foreach ($classDefinitions as $class) {
            [$groupName, $type] = $this->resolveGroupInfo($class);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'classes' => [],
                    'type' => $type,
                ];
            }

            $groups[$groupName]['classes'][] = $class;
        }

        return $groups;
    }

    /**
     * @return array{string, string} [groupName, type]
     */
    private function resolveGroupInfo(ClassDefinition $class): array
    {
        if ($class->getGroup()) {
            return [$class->getGroup(), 'with-group'];
        }

        return [$this->extractGroupNameFromClassName($class->getName()), 'without-group'];
    }

    private function extractGroupNameFromClassName(string $className): string
    {
        if (preg_match('@^([A-Za-z][^A-Z]*)@', $className, $matches)) {
            return $matches[0];
        }

        return $className;
    }

    private function sortGroups(array $groups): array
    {
        $types = array_column($groups, 'type');
        array_multisort($types, SORT_DESC, array_keys($groups), SORT_ASC, $groups);

        return $groups;
    }

    private function getGroupedNodes(array $groups): array
    {
        $hydrated = [];
        foreach ($groups as $groupName => $groupData) {
            if ($groupData['type'] === 'without-group' && count($groupData['classes']) === 1) {
                $hydrated[] = $this->nodeHydrator->hydrate($groupData['classes'][0]);

                continue;
            }

            $children = [];
            foreach ($groupData['classes'] as $class) {
                $children[] = $this->nodeHydrator->hydrate($class);
            }

            $hydrated[] = $this->folderNodeHydrator->hydrate($groupName, $children);
        }

        return $hydrated;
    }
}
