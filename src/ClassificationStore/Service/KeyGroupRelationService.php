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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\KeyGroupRelationEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class KeyGroupRelationService implements KeyGroupRelationServiceInterface
{
    public function __construct(
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private GroupServiceInterface $groupService,
        private KeyGroupRelationHydratorInterface $keyGroupRelationHydrator,
        private GroupConfigResolverInterface $groupConfigResolver,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getKeyGroupRelations(ListClassificationStoreParameter $parameter): Collection
    {
        $allowedGroupIds =  $this->groupService->getAllowedGroupIds($parameter);

        if (count($allowedGroupIds) === 0) {
            $allowedGroupIds = null;
        }

        $hydratedKeyGroupRelations = [];
        $keyGroupRelations = $this->keyGroupRelationRepository->getPaginatedKeyGroupRelationByStore(
            $parameter->getStoreId(),
            $parameter,
            $allowedGroupIds
        );

        foreach ($keyGroupRelations as $keyGroupRelation) {
            $groupConfig = $this->groupConfigResolver->getById($keyGroupRelation->getGroupId());

            $hydratedKeyGroupRelation = $this->keyGroupRelationHydrator->hydrate($keyGroupRelation, $groupConfig);

            $this->eventDispatcher->dispatch(
                new KeyGroupRelationEvent($hydratedKeyGroupRelation),
                KeyGroupRelationEvent::EVENT_NAME
            );

            $hydratedKeyGroupRelations[] = $hydratedKeyGroupRelation;
        }

        return new Collection(
            $this->keyGroupRelationRepository->getCountByStoreId($parameter->getStoreId(), $allowedGroupIds),
            $hydratedKeyGroupRelations
        );
    }
}
