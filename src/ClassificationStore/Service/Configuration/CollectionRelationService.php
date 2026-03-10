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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\CollectionRelationDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\CollectionRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\GroupInfoResolverTrait;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class CollectionRelationService implements CollectionRelationServiceInterface
{
    use GroupInfoResolverTrait;

    public function __construct(
        private CollRelationRepositoryInterface $collectionRelationRepository,
        private CollectionRelationHydratorInterface $collectionRelationHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private GroupRepositoryInterface $groupConfigurationRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listCollectionRelations(
        CollectionFilterParameter $parameters,
        int $colId,
    ): Collection {
        $listing = $this->collectionRelationRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters),
            $colId
        );
        $relations = $listing->load();
        $items = [];

        foreach ($relations as $relation) {
            [$groupName, $groupDescription] = $this->resolveGroupInfo(
                $relation->getGroupId(),
                $this->groupConfigurationRepository
            );
            $items[] = $this->getHydratedCollectionRelationDetail($relation, $groupName, $groupDescription);
        }

        return new Collection(
            $listing->getTotalCount(),
            $items
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdateCollectionRelation(
        CollectionRelationCreate $parameters
    ): CollectionRelationDetail {
        $relation = $this->collectionRelationRepository->createOrUpdate(
            $parameters->getColId(),
            $parameters->getGroupId(),
            $parameters->getSorter()
        );

        [$groupName, $groupDescription] = $this->resolveGroupInfo(
            $relation->getGroupId(),
            $this->groupConfigurationRepository
        );

        return $this->getHydratedCollectionRelationDetail($relation, $groupName, $groupDescription);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCollectionRelation(int $colId, int $groupId): void
    {
        $this->collectionRelationRepository->delete($colId, $groupId);
    }

    private function getHydratedCollectionRelationDetail(
        CollectionGroupRelation $relation,
        ?string $groupName = null,
        ?string $groupDescription = null,
    ): CollectionRelationDetail {
        $detail = $this->collectionRelationHydrator->hydrateCollectionRelationDetail(
            $relation,
            $groupName,
            $groupDescription,
        );
        $this->eventDispatcher->dispatch(
            new CollectionRelationDetailEvent($detail),
            CollectionRelationDetailEvent::EVENT_NAME
        );

        return $detail;
    }
}
