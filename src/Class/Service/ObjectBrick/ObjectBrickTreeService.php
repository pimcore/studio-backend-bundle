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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\TreeEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\TreeFolderNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\TreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickTreeNodeFolder;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class ObjectBrickTreeService implements ObjectBrickTreeServiceInterface
{
    public function __construct(
        private ObjectBrickRepositoryInterface $objectBrickRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TreeNodeHydratorInterface $treeNodeHydrator,
        private TreeFolderNodeHydratorInterface $treeFolderNodeHydrator
    ) {
    }

    public function getTree(): array
    {
        $definitions = $this->objectBrickRepository->listObjectBricks();
        $groups = $this->getGroups($definitions);

        if (empty($groups)) {
            return [];
        }

        $groups = $this->sortGroups($groups);

        return $this->getGroupedNodes($groups);
    }

    /**
     * @param Definition[] $definitions
     */
    private function getGroups(array $definitions): array
    {
        $groups = [];

        foreach ($definitions as $definition) {
            [$groupName, $type] = $this->resolveGroupInfo($definition);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'definitions' => [],
                    'type' => $type,
                ];
            }

            $groups[$groupName]['definitions'][] = $definition;
        }

        return $groups;
    }

    /**
     * @return array{string, string}
     */
    private function resolveGroupInfo(Definition $definition): array
    {
        if ($definition->getGroup()) {
            return [$definition->getGroup(), 'with-group'];
        }

        return [$this->extractGroupNameFromKey($definition->getKey()), 'without-group'];
    }

    private function extractGroupNameFromKey(string $key): string
    {
        if (preg_match('@^([A-Za-z][^A-Z]*)@', $key, $matches)) {
            return $matches[0];
        }

        return $key;
    }

    private function sortGroups(array $groups): array
    {
        uksort($groups, static function (string $a, string $b) use ($groups): int {
            $typeComparison = strcmp($groups[$b]['type'], $groups[$a]['type']);

            if ($typeComparison !== 0) {
                return $typeComparison;
            }

            return strnatcasecmp($a, $b);
        });

        return $groups;
    }

    private function getGroupedNodes(array $groups): array
    {
        $result = [];

        foreach ($groups as $groupName => $groupData) {
            if ($groupData['type'] === 'without-group' && count($groupData['definitions']) === 1) {
                $result[] = $this->hydrateNode($groupData['definitions'][0]);

                continue;
            }

            $children = [];
            foreach ($groupData['definitions'] as $definition) {
                $children[] = $this->hydrateNode($definition);
            }

            $result[] = $this->hydrateFolderNode($groupName, $children);
        }

        return $result;
    }

    private function hydrateNode(Definition $definition): ObjectBrickTreeNode
    {
        $node = $this->treeNodeHydrator->hydrate($definition);
        $this->dispatchTreeEvent($node);

        return $node;
    }

    private function hydrateFolderNode(
        string $groupName,
        array $children
    ): ObjectBrickTreeNodeFolder {
        $node = $this->treeFolderNodeHydrator->hydrate($groupName, $children);
        $this->dispatchTreeEvent($node);

        return $node;
    }

    private function dispatchTreeEvent(ObjectBrickTreeNode|ObjectBrickTreeNodeFolder $node): void
    {
        $this->eventDispatcher->dispatch(
            new TreeEvent($node),
            TreeEvent::EVENT_NAME
        );
    }
}
