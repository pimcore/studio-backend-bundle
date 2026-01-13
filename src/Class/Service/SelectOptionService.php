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

use Pimcore\Bundle\StudioBackendBundle\Class\Event\SelectOption\TreeEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption\TreeFolderHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption\TreeItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTree;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTreeFolder;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Config\Listing;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class SelectOptionService implements SelectOptionServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TreeItemHydratorInterface $nodeHydrator,
        private TreeFolderHydratorInterface $folderNodeHydrator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(bool $grouped = false): array
    {
        $selectOptionConfigs = new Listing();

        if ($grouped === false) {
            return $this->getUngroupedTree($selectOptionConfigs);
        }

        $groups = $this->getGroups($selectOptionConfigs);
        if (empty($groups)) {
            return [];
        }

        $groups = $this->sortGroups($groups);

        return $this->getGroupedNodes($groups);
    }

    private function getUngroupedTree(Listing $configs): array
    {
        $hydrated = [];

        /** @var Config $config */
        foreach ($configs as $config) {
            $hydrated[] = $this->hydrateConfig($config);
        }

        return $hydrated;
    }

    private function getGroups(Listing $configs): array
    {
        $groups = [];

        /** @var Config $config */
        foreach ($configs as $config) {
            [$groupName, $type] = $this->resolveGroupInfo($config);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'configs' => [],
                    'type' => $type,
                ];
            }

            $groups[$groupName]['configs'][] = $config;
        }

        return $groups;
    }

    /**
     * @return array{string, string} [groupName, type]
     */
    private function resolveGroupInfo(Config $config): array
    {
        if ($config->hasGroup()) {
            return [$config->getGroup(), 'with-group'];
        }

        return [$config->getId(), 'without-group'];
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
            if ($groupData['type'] === 'without-group' && count($groupData['configs']) === 1) {
                $hydrated[] = $this->hydrateConfig($groupData['configs'][0]);

                continue;
            }

            $children = [];
            foreach ($groupData['configs'] as $config) {
                $children[] = $this->hydrateConfig($config);
            }

            $hydrated[] = $this->hydrateFolderNode($groupName, $children);
        }

        return $hydrated;
    }

    private function hydrateConfig(Config $config): SelectOptionTree
    {
        $treeNode = $this->nodeHydrator->hydrate($config);
        $this->eventDispatcher->dispatch(
            new TreeEvent($treeNode),
            TreeEvent::EVENT_NAME
        );

        return $treeNode;
    }

    private function hydrateFolderNode(string $name, array $children): SelectOptionTreeFolder
    {
        $treeNode = $this->folderNodeHydrator->hydrate($name, $children);
        $this->eventDispatcher->dispatch(
            new TreeEvent($treeNode),
            TreeEvent::EVENT_NAME
        );

        return $treeNode;
    }
}
