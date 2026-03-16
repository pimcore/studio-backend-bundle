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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\GroupDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class GroupService implements GroupServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private GroupRepositoryInterface $groupConfigurationRepository,
        private GroupHydratorInterface $groupHydrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listGroups(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection {
        $listing = $this->groupConfigurationRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters),
            $storeId
        );
        $configs = $listing->load();
        $items = [];

        foreach ($configs as $config) {
            $items[] = $this->getHydratedGroupDetail($config);
        }

        return new Collection(
            $listing->getTotalCount(),
            $items
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createGroup(GroupCreate $parameters): GroupDetail
    {
        $groupConfig = $this->groupConfigurationRepository->create(
            $parameters->getName(),
            $parameters->getStoreId()
        );

        return $this->getHydratedGroupDetail($groupConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function updateGroup(int $id, GroupUpdate $parameters): GroupDetail
    {
        $groupConfig = $this->groupConfigurationRepository->update(
            $id,
            $parameters->getName(),
            $parameters->getDescription(),
        );

        return $this->getHydratedGroupDetail($groupConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGroup(int $id): void
    {
        $this->groupConfigurationRepository->delete($id);
    }

    private function getHydratedGroupDetail(GroupConfig $groupConfig): GroupDetail
    {
        $groupDetail = $this->groupHydrator->hydrateGroupDetail($groupConfig);
        $this->eventDispatcher->dispatch(
            new GroupDetailEvent($groupDetail),
            GroupDetailEvent::EVENT_NAME
        );

        return $groupDetail;
    }
}
