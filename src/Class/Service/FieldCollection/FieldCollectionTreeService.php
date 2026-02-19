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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\TreeEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\TreeFolderNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\TreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionTreeNodeFolder;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function in_array;

/**
 * @internal
 */
final readonly class FieldCollectionTreeService implements FieldCollectionTreeServiceInterface
{
    public function __construct(
        private FieldCollectionRepositoryInterface $fieldCollectionRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TreeNodeHydratorInterface $treeNodeHydrator,
        private TreeFolderNodeHydratorInterface $treeFolderNodeHydrator
    ) {
    }

    public function getTree(?array $allowedTypes = null): array
    {
        $definitions = $this->fieldCollectionRepository->listFieldCollections();

        if ($allowedTypes !== null) {
            $definitions = array_filter(
                $definitions,
                static fn (Definition $definition): bool => in_array(
                    $definition->getKey(),
                    $allowedTypes,
                    true
                )
            );
        }

        if (empty($definitions)) {
            return [];
        }

        return $this->getGroupedTree($definitions);
    }

    /**
     * @param Definition[] $definitions
     */
    private function getGroupedTree(array $definitions): array
    {
        $groups = [];
        $ungrouped = [];

        foreach ($definitions as $definition) {
            $group = $definition->getGroup();

            if ($group) {
                if (!isset($groups[$group])) {
                    $groups[$group] = [];
                }

                $groups[$group][] = $definition;
            } else {
                $ungrouped[] = $definition;
            }
        }

        $sortable = [];

        foreach ($ungrouped as $definition) {
            $sortable[] = ['sortKey' => $definition->getKey(), 'type' => 'node', 'data' => $definition];
        }

        foreach ($groups as $groupName => $groupDefinitions) {
            $sortable[] = ['sortKey' => $groupName, 'type' => 'folder', 'data' => $groupDefinitions];
        }

        usort($sortable, static fn (array $a, array $b): int => strnatcasecmp($a['sortKey'], $b['sortKey']));

        $result = [];

        foreach ($sortable as $entry) {
            if ($entry['type'] === 'node') {
                $result[] = $this->hydrateNode($entry['data']);

                continue;
            }

            $result[] = $this->hydrateFolderNode($entry['sortKey'], $entry['data']);
        }

        return $result;
    }

    private function hydrateNode(Definition $definition): FieldCollectionTreeNode
    {
        $node = $this->treeNodeHydrator->hydrate($definition);
        $this->dispatchTreeEvent($node);

        return $node;
    }

    private function hydrateFolderNode(string $groupName, array $definitions): FieldCollectionTreeNodeFolder
    {
        $children = array_map(
            fn (Definition $definition): FieldCollectionTreeNode => $this->treeNodeHydrator->hydrate($definition),
            $definitions
        );

        $node = $this->treeFolderNodeHydrator->hydrate($groupName, $children);
        $this->dispatchTreeEvent($node);

        return $node;
    }

    private function dispatchTreeEvent(FieldCollectionTreeNode|FieldCollectionTreeNodeFolder $node): void
    {
        $this->eventDispatcher->dispatch(
            new TreeEvent($node),
            TreeEvent::EVENT_NAME
        );
    }
}
