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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\GroupEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\GroupLayoutEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function array_values;
use function count;

/**
 * @internal
 */
final readonly class GroupService implements GroupServiceInterface
{
    public function __construct(
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private GroupConfigRepositoryInterface $groupConfigRepository,
        private EventDispatcherInterface $eventDispatcher,
        private GroupHydratorInterface $groupHydrator,
        private KeyRelationResolverInterface $keyRelationResolver,
        private KeyGroupLayoutServiceInterface $keyGroupLayoutService,
        private GroupLayoutHydratorInterface $groupLayoutHydrator,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private SubGroupRepositoryInterface $subGroupConfigurationRepository,
        private LabelLanguageResolverInterface $labelLanguageResolver,
    ) {
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
        $keys = $this->keyRelationResolver->getByGroup($groupId);

        $object = null;
        // `!== null`, not truthiness: only an omitted objectId is served without an object in
        // context. An objectId of 0 was supplied and does not resolve, so it is still a 404.
        if ($layoutParameter->getObjectId() !== null) {
            $object = $this->concreteObjectResolver->getById($layoutParameter->getObjectId());

            if (!$object) {
                throw new NotFoundException('object', $layoutParameter->getObjectId());
            }
        }

        $language = $this->labelLanguageResolver->getLabelLanguage();

        // One statement for the whole group, instead of a label query per key.
        $this->keyGroupLayoutService->preloadKeyTitles(
            array_map(static fn (KeyGroupRelation $key): int => $key->getKeyId(), $keys),
            $language
        );

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
                title: $this->keyGroupLayoutService->getKeyTitle($key->getKeyId(), $language),
                subGroup: $key->getSubGroup(),
            );
        }

        $group = $this->groupConfigRepository->getById($groupId);
        $groupLayout = $this->groupLayoutHydrator->hydrate(
            $keyLayouts,
            $group,
            $this->resolveSubGroups($keys, $group->getStoreId())
        );

        $this->eventDispatcher->dispatch(
            new GroupLayoutEvent($groupLayout),
            GroupLayoutEvent::EVENT_NAME
        );

        return $groupLayout;
    }

    /**
     * The sub groups the keys of the group carry, each positioned by the first key carrying it: the
     * relations arrive in sorter then id order, so the order follows the keys and is never stored.
     * An identifier with no row in the store's vocabulary is left out rather than fabricated from
     * the identifier.
     *
     * @param KeyGroupRelation[] $keys
     *
     * @return SubGroupLayout[]
     */
    private function resolveSubGroups(array $keys, int $storeId): array
    {
        $identifiers = [];
        foreach ($keys as $key) {
            $identifier = $key->getSubGroup();
            if ($identifier === null) {
                continue;
            }

            // valued as well as keyed: array_keys() would hand back an all digit identifier as an int
            $identifiers[$identifier] = $identifier;
        }

        if (empty($identifiers)) {
            return [];
        }

        $configs = $this->subGroupConfigurationRepository->getByIdentifiers(
            $storeId,
            array_values($identifiers)
        );

        $subGroups = [];
        foreach ($identifiers as $identifier) {
            $config = $configs[$identifier] ?? null;
            if (!$config) {
                continue;
            }

            $subGroups[] = new SubGroupLayout(
                identifier: $identifier,
                name: $config->getName(),
                description: $config->getDescription(),
            );
        }

        return $subGroups;
    }
}
