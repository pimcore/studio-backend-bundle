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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\SelectOption\TreeEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption\TreeFolderHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption\TreeItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\SelectOptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTree;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTreeFolder;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class TreeService implements TreeServiceInterface
{
    public function __construct(
        private SelectOptionRepositoryInterface $selectOptionRepository,
        private EventDispatcherInterface $eventDispatcher,
        private TreeItemHydratorInterface $nodeHydrator,
        private TreeFolderHydratorInterface $folderNodeHydrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(bool $grouped = false): array
    {
        $selectOptionConfigs = $this->selectOptionRepository->listSelectOptions();

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

    /**
     * @param Config[] $configs
     */
    private function getUngroupedTree(array $configs): array
    {
        $hydrated = [];

        foreach ($configs as $config) {
            $hydrated[] = $this->hydrateConfig($config);
        }

        return $hydrated;
    }

    /**
     * @param Config[] $configs
     */
    private function getGroups(array $configs): array
    {
        $groups = [];

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
        uksort($groups, static function (string $a, string $b) use ($groups): int {
            $typeComparison = $groups[$b]['type'] <=> $groups[$a]['type'];

            return $typeComparison !== 0 ? $typeComparison : $a <=> $b;
        });

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
