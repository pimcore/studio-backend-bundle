<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\CollectionEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\CollectionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionRelationsRepositoryInterface;
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
     * {@inheritDoc}
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
            $allowedCollectionIds
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
