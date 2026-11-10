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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\GroupInfoResolverTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
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
        private KeyRelationResolverInterface $keyRelationResolver,
        private GroupRepositoryInterface $groupConfigurationRepository,
        private KeyRepositoryInterface $keyConfigurationRepository,
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
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
            [$keyName, $keyDescription, $keyLocalized, $keyConfig] = $this->resolveKeyInfo($relation->getKeyId());
            $items[] = $this->getHydratedKeyGroupRelationDetail(
                $relation,
                $keyName,
                $keyDescription,
                $keyLocalized,
                $keyConfig
            );
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
        try {
            $relation = $this->keyGroupRelationRepository->createOrUpdate($parameters);
        } finally {
            $this->keyRelationResolver->clear();
        }

        [$keyName, $keyDescription, $keyLocalized, $keyConfig] = $this->resolveKeyInfo($relation->getKeyId());

        return $this->getHydratedKeyGroupRelationDetail(
            $relation,
            $keyName,
            $keyDescription,
            $keyLocalized,
            $keyConfig
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteKeyGroupRelation(int $keyId, int $groupId): void
    {
        try {
            $this->keyGroupRelationRepository->delete($keyId, $groupId);
        } finally {
            $this->keyRelationResolver->clear();
        }
    }

    private function getHydratedKeyGroupRelationDetail(
        KeyGroupRelation $relation,
        ?string $keyName = null,
        ?string $keyDescription = null,
        ?bool $keyLocalized = null,
        ?KeyConfig $keyConfig = null
    ): KeyGroupRelationDetail {
        $detail = $this->keyGroupRelationHydrator->hydrateKeyGroupRelationDetail(
            $relation,
            $keyName,
            $keyDescription,
            $this->resolveGroupName($relation->getGroupId(), $this->groupConfigurationRepository),
            $keyLocalized,
            $keyConfig,
        );
        $this->eventDispatcher->dispatch(
            new KeyGroupRelationDetailEvent($detail),
            KeyGroupRelationDetailEvent::EVENT_NAME
        );

        return $detail;
    }

    /**
     * The key's `localized` flag comes off the configuration this already loads for the name and the
     * description: core's relation has no property of its own for it, so there is nothing on the
     * relation to read it from.
     *
     * @return array{0: ?string, 1: ?string, 2: ?bool, 3: ?KeyConfig} [keyName, keyDescription, keyLocalized, keyConfig]
     */
    private function resolveKeyInfo(int $keyId): array
    {
        try {
            $keyConfig = $this->keyConfigurationRepository->getById($keyId);

            return [
                $keyConfig->getName(),
                $keyConfig->getDescription(),
                $this->keyLocalizationResolver->getLocalized($keyConfig),
                $keyConfig,
            ];
        } catch (NotFoundException) {
            return [null, null, null, null];
        }
    }
}
