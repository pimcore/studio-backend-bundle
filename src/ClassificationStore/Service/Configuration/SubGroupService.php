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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\SubGroupDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\SubGroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SubGroupService implements SubGroupServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private SubGroupRepositoryInterface $subGroupConfigurationRepository,
        private SubGroupHydratorInterface $subGroupHydrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listSubGroups(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection {
        $listing = $this->subGroupConfigurationRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters),
            $storeId
        );
        $configs = $listing->load();
        $items = [];

        foreach ($configs as $config) {
            $items[] = $this->getHydratedSubGroupDetail($config);
        }

        return new Collection(
            $listing->getTotalCount(),
            $items
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createSubGroup(SubGroupCreate $parameters): SubGroupDetail
    {
        $subGroupConfig = $this->subGroupConfigurationRepository->create(
            $parameters->getName(),
            $parameters->getStoreId()
        );

        return $this->getHydratedSubGroupDetail($subGroupConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSubGroup(int $id, SubGroupUpdate $parameters): SubGroupDetail
    {
        $subGroupConfig = $this->subGroupConfigurationRepository->update(
            $id,
            $parameters->getName(),
            $parameters->getDescription(),
        );

        return $this->getHydratedSubGroupDetail($subGroupConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSubGroup(int $id): void
    {
        $this->subGroupConfigurationRepository->delete($id);
    }

    private function getHydratedSubGroupDetail(SubGroupConfig $subGroupConfig): SubGroupDetail
    {
        $subGroupDetail = $this->subGroupHydrator->hydrateSubGroupDetail($subGroupConfig);
        $this->eventDispatcher->dispatch(
            new SubGroupDetailEvent($subGroupDetail),
            SubGroupDetailEvent::EVENT_NAME
        );

        return $subGroupDetail;
    }
}
