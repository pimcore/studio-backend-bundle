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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\CollectionDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\CollectionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionUpdate;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CollectionService implements CollectionServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private CollectionRepositoryInterface $collectionConfigurationRepository,
        private CollectionHydratorInterface $collectionHydrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listCollections(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection {
        $listing = $this->collectionConfigurationRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters),
            $storeId
        );
        $configs = $listing->load();
        $items = [];

        foreach ($configs as $config) {
            $items[] = $this->getHydratedCollectionDetail($config);
        }

        return new Collection(
            $listing->getTotalCount(),
            $items
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createCollection(CollectionCreate $parameters): CollectionDetail
    {
        $collectionConfig = $this->collectionConfigurationRepository->create(
            $parameters->getName(),
            $parameters->getStoreId()
        );

        return $this->getHydratedCollectionDetail($collectionConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function updateCollection(int $id, CollectionUpdate $parameters): CollectionDetail
    {
        $collectionConfig = $this->collectionConfigurationRepository->update(
            $id,
            $parameters->getName(),
            $parameters->getDescription(),
        );

        return $this->getHydratedCollectionDetail($collectionConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCollection(int $id): void
    {
        $this->collectionConfigurationRepository->delete($id);
    }

    private function getHydratedCollectionDetail(CollectionConfig $collectionConfig): CollectionDetail
    {
        $collectionDetail = $this->collectionHydrator->hydrateCollectionDetail($collectionConfig);
        $this->eventDispatcher->dispatch(
            new CollectionDetailEvent($collectionDetail),
            CollectionDetailEvent::EVENT_NAME
        );

        return $collectionDetail;
    }
}
