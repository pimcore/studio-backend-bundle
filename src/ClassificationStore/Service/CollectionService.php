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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\CollectionEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\CollectionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionRelationsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\CollectionLayout;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class CollectionService implements CollectionServiceInterface
{
    public function __construct(
        private CollectionRelationsRepositoryInterface $collectionRelationsRepository,
        private CollectionConfigRepositoryInterface $collectionConfigRepository,
        private CollectionHydratorInterface $collectionHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private GroupServiceInterface $groupService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getCollections(ListClassificationStoreParameter $parameter): Collection
    {
        $allowedCollectionIds = $this->getAllowedCollectionIds($parameter);

        if (count($allowedCollectionIds) === 0) {
            $allowedCollectionIds = null;
        }

        $collections = $this->collectionConfigRepository->getPaginatedCollectionsByStore(
            $parameter->getStoreId(),
            $parameter,
            $allowedCollectionIds,
            $parameter->getSearchTerm()
        );

        $hydratedCollections = [];
        foreach ($collections as $collection) {
            $hydratedCollection = $this->collectionHydrator->hydrate($collection);

            $this->eventDispatcher->dispatch(
                new CollectionEvent($hydratedCollection),
                CollectionEvent::EVENT_NAME
            );

            $hydratedCollections[] = $hydratedCollection;
        }

        return new Collection(
            totalItems: $this->collectionConfigRepository->getCountByStoreId($parameter->getStoreId()),
            items: $hydratedCollections
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinition(int $collectionId, LayoutParameter $layoutParameter): CollectionLayout
    {
        $groups = $this->collectionRelationsRepository->getFromCollection($collectionId);

        $layouts = [];
        foreach ($groups as $group) {
            $layouts[] = $this->groupService->getLayoutDefinition($group->getGroupId(), $layoutParameter);
        }

        return new CollectionLayout(
            groups: $layouts
        );
    }

    /**
     * @return array<int, int>
     *
     * @throws Exception
     * @throws NotFoundException
     * @throws DatabaseException
     */
    private function getAllowedCollectionIds(ListClassificationStoreParameter $parameter): array
    {
        $allowedGroupIds = $this->groupService->getAllowedGroupIds($parameter);

        return $this->collectionRelationsRepository->getCollectionIdsWith($allowedGroupIds);
    }
}
