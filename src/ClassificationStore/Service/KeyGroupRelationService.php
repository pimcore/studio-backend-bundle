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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\KeyGroupRelationEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;
use Pimcore\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
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
        private KeyConfigResolverInterface $keyConfigResolver,
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
    ) {
    }

    /**
     * {@inheritdoc}
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
            $allowedGroupIds,
            $parameter->getSearchTerm()
        );

        foreach ($keyGroupRelations as $keyGroupRelation) {
            $groupConfig = $this->groupConfigResolver->getById($keyGroupRelation->getGroupId());

            $hydratedKeyGroupRelation = $this->keyGroupRelationHydrator->hydrate(
                $keyGroupRelation,
                $groupConfig,
                $this->resolveKeyLocalized($keyGroupRelation->getKeyId())
            );

            $this->eventDispatcher->dispatch(
                new KeyGroupRelationEvent($hydratedKeyGroupRelation),
                KeyGroupRelationEvent::EVENT_NAME
            );

            $hydratedKeyGroupRelations[] = $hydratedKeyGroupRelation;
        }

        return new Collection(
            $this->keyGroupRelationRepository->getCountByStoreId(
                $parameter->getStoreId(),
                $allowedGroupIds,
                $parameter->getSearchTerm()
            ),
            $hydratedKeyGroupRelations
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinition(
        KeyGroupRelation $keyGroupRelation,
        Concrete $object,
        string $fieldName
    ): EncryptedField|Data {
        $definition = json_decode($keyGroupRelation->getDefinition(), true);
        $definition = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson(
            $definition,
            $keyGroupRelation->getType()
        );

        if (method_exists($definition, '__wakeup')) {
            $definition->__wakeup();
        }

        if ($definition instanceof LayoutDefinitionEnrichmentInterface) {
            $context['object'] = $object;
            $context['class'] = $object->getClass();
            $context['ownerType'] = 'classificationstore';
            $context['ownerName'] = $fieldName;
            $context['keyId'] = $keyGroupRelation->getKeyId();
            $context['groupId'] = $keyGroupRelation->getGroupId();
            $context['keyDefinition'] = $definition;

            $definition = $definition->enrichLayoutDefinition($object, $context);
        }

        return $definition;
    }

    /**
     * The key's own flag, read off the key configuration: core's relation listing selects the key's
     * columns but keeps only those it has a property for, and it has none for this one. A key the
     * configuration no longer carries reports nothing rather than failing the whole listing.
     */
    private function resolveKeyLocalized(int $keyId): ?bool
    {
        $keyConfig = $this->keyConfigResolver->getById($keyId);

        if (!$keyConfig instanceof KeyConfig) {
            return null;
        }

        return $this->keyLocalizationResolver->getLocalized($keyConfig);
    }
}
