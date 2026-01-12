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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\GroupEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\GroupLayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\StoreConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\StoreConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\StoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class StoreService implements StoreServiceInterface, GroupServiceInterface
{
    public function __construct(
        private StoreRepositoryInterface $storeRepository,
        private ConcreteObjectResolver $concreteObjectResolver,
        private GroupConfigRepositoryInterface $groupConfigRepository,
        private EventDispatcherInterface $eventDispatcher,
        private GroupHydratorInterface $groupHydrator,
        private StoreConfigHydratorInterface $storeConfigHydrator,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyGroupLayoutServiceInterface $keyGroupLayoutService,
        private GroupLayoutHydratorInterface $groupLayoutHydrator,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
    ) {
    }

    public function listStores(): Collection
    {
        $storeConfigs = $this->storeRepository->listStores();
        $stores = [];
        foreach ($storeConfigs as $storeConfig) {
            $store = $this->storeConfigHydrator->hydrate($storeConfig);
            $this->eventDispatcher->dispatch(
                new StoreConfigEvent($store),
                StoreConfigEvent::EVENT_NAME
            );
            $stores[] = $store;
        }

        return new Collection(count($stores), $stores);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(ListClassificationStoreParameter $parameter): Collection
    {
        $allowedGroupIds = $this->getAllowedGroupIds($parameter);

        if (count($allowedGroupIds) === 0) {
            $allowedGroupIds = null;
        }

        $groups = $this->groupConfigRepository->getPaginatedGroupsByStore(
            $parameter->getStoreId(),
            $parameter,
            $allowedGroupIds,
            $parameter->getSearchTerm()
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
     * {@inheritdoc}
     */
    public function getAllowedGroupIds(ListClassificationStoreParameter $parameter): array
    {
        if (!$parameter->getClassId()) {
            return [];
        }

        $class = $this->classDefinitionResolver->getById($parameter->getClassId());

        if (!$class) {
            throw new NotFoundException('Class', $parameter->getClassId());
        }

        $fieldDefinition = $class->getFieldDefinition($parameter->getFieldName());

        if (!$fieldDefinition instanceof Classificationstore) {
            throw new NotFoundException('field', $parameter->getFieldName());
        }

        return $fieldDefinition->getAllowedGroupIds();
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinition(int $groupId, LayoutParameter $layoutParameter): GroupLayout
    {
        $keys = $this->keyGroupRelationRepository->getByGroupId($groupId);
        $object = $this->concreteObjectResolver->getById($layoutParameter->getObjectId());

        if (!$object) {
            throw new NotFoundException('object', $layoutParameter->getObjectId());
        }

        $keyLayouts = [];
        foreach ($keys as $key) {
            $definition = $this->keyGroupLayoutService->getLayoutDefinition(
                $key,
                $layoutParameter->getFieldName(),
                $object
            );

            $keyLayouts[] = new KeyLayout(
                id: $key->getKeyId(),
                name: $key->getName(),
                description: $key->getDescription(),
                definition: $definition,
            );
        }

        $group = $this->groupConfigRepository->getById($groupId);
        $groupLayout = $this->groupLayoutHydrator->hydrate($keyLayouts, $group);

        $this->eventDispatcher->dispatch(
            new GroupLayoutEvent($groupLayout),
            GroupLayoutEvent::EVENT_NAME
        );

        return $groupLayout;
    }
}
