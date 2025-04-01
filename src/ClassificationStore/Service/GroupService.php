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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\GroupEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class GroupService implements GroupServiceInterface
{
    public function __construct(
        private ConcreteObjectResolver $concreteObjectResolver,
        private GroupConfigRepositoryInterface $groupConfigRepository,
        private EventDispatcherInterface $eventDispatcher,
        private GroupHydratorInterface $groupHydrator,
    ) {
    }

    public function getGroups(ListClassificationStoreParameter $parameter): Collection
    {
        $allowedGroupIds = $this->getAllowedGroupIds($parameter);

        if (count($allowedGroupIds) === 0) {
            $allowedGroupIds = null;
        }

        $groups = $this->groupConfigRepository->getPaginatedGroupsByStore(
            $parameter->getStoreId(),
            $parameter,
            $allowedGroupIds
        );

        $hydratedGroups = [];
        foreach ($groups as $group) {
            $hydratedGroup = $this->groupHydrator->hydrate($group);
            $this->eventDispatcher->dispatch(
                new GroupEvent($hydratedGroup),
                GroupEvent::EVENT_NAME
            );
            $hydratedGroups[] = $hydratedGroup;
        }

        return new Collection(
            totalItems: $this->groupConfigRepository->getCountByStoreId($parameter->getStoreId()),
            items: $hydratedGroups
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllowedGroupIds(ListClassificationStoreParameter $parameter): array
    {
        if (!$parameter->getObjectId()) {
            return [];
        }

        $object = $this->concreteObjectResolver->getById($parameter->getObjectId());

        if (!$object) {
            throw new NotFoundException('object', $parameter->getObjectId());
        }

        $class = $object->getClass();
        $fieldDefinition = $class->getFieldDefinition($parameter->getFieldName());

        if (!$fieldDefinition instanceof Classificationstore) {
            throw new NotFoundException('field', $parameter->getFieldName());
        }

        return $fieldDefinition->getAllowedGroupIds();
    }
}
