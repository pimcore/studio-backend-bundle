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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\KeyGroupRelationDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\GroupInfoResolverTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class KeyGroupRelationService implements KeyGroupRelationServiceInterface
{
    use GroupInfoResolverTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private KeyGroupRelationRepositoryInterface $keyGroupRelationRepository,
        private KeyGroupRelationHydratorInterface $keyGroupRelationHydrator,
        private GroupRepositoryInterface $groupConfigurationRepository,
        private KeyRepositoryInterface $keyConfigurationRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listKeyGroupRelations(
        CollectionFilterParameter $parameters,
        int $groupId,
    ): Collection {
        $listing = $this->keyGroupRelationRepository->getListingByGroupId(
            $this->filterMapper->getFilterParameters($parameters),
            $groupId
        );
        $relations = $listing->load();
        $items = [];

        foreach ($relations as $relation) {
            [$keyName, $keyDescription] = $this->resolveKeyInfo($relation->getKeyId());
            $items[] = $this->getHydratedKeyGroupRelationDetail($relation, $keyName, $keyDescription);
        }

        return new Collection(
            $listing->getTotalCount(),
            $items
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdateKeyGroupRelation(
        KeyGroupRelationCreate $parameters
    ): KeyGroupRelationDetail {
        $relation = $this->keyGroupRelationRepository->createOrUpdate(
            $parameters->getKeyId(),
            $parameters->getGroupId(),
            $parameters->getSorter(),
            $parameters->isMandatory()
        );

        [$keyName, $keyDescription] = $this->resolveKeyInfo($relation->getKeyId());

        return $this->getHydratedKeyGroupRelationDetail($relation, $keyName, $keyDescription);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteKeyGroupRelation(int $keyId, int $groupId): void
    {
        $this->keyGroupRelationRepository->delete($keyId, $groupId);
    }

    private function getHydratedKeyGroupRelationDetail(
        KeyGroupRelation $relation,
        ?string $keyName = null,
        ?string $keyDescription = null
    ): KeyGroupRelationDetail {
        $detail = $this->keyGroupRelationHydrator->hydrateKeyGroupRelationDetail(
            $relation,
            $keyName,
            $keyDescription,
            $this->resolveGroupName($relation->getGroupId()),
        );
        $this->eventDispatcher->dispatch(
            new KeyGroupRelationDetailEvent($detail),
            KeyGroupRelationDetailEvent::EVENT_NAME
        );

        return $detail;
    }

    /**
     * @return array{0: ?string, 1: ?string} [keyName, keyDescription]
     */
    private function resolveKeyInfo(int $keyId): array
    {
        try {
            $keyConfig = $this->keyConfigurationRepository->getById($keyId);

            return [$keyConfig->getName(), $keyConfig->getDescription()];
        } catch (NotFoundException) {
            return [null, null];
        }
    }
}
