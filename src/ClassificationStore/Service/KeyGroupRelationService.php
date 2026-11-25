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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\KeyGroupRelationEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
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
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
        private LabelResolverInterface $labelResolver,
        private LabelLanguageResolverInterface $labelLanguageResolver,
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

        // the flag and the title of every key on the page in one statement, never one per relation
        $keyConfigs = $this->keyGroupRelationRepository->getKeyConfigs($this->keyIds($keyGroupRelations));
        $this->preloadKeyTitles($keyGroupRelations);

        foreach ($keyGroupRelations as $keyGroupRelation) {
            $groupConfig = $this->groupConfigResolver->getById($keyGroupRelation->getGroupId());
            $keyConfig = $keyConfigs[$keyGroupRelation->getKeyId()] ?? null;

            $hydratedKeyGroupRelation = $this->keyGroupRelationHydrator->hydrate(
                $keyGroupRelation,
                $groupConfig,
                $keyConfig === null ? null : $this->keyLocalizationResolver->getLocalized($keyConfig),
                $keyConfig
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
     * The labels of every key on the page in one statement, so the hydrator's title lookups cost nothing.
     *
     * @param KeyGroupRelation[] $keyGroupRelations
     */
    private function preloadKeyTitles(array $keyGroupRelations): void
    {
        if ($keyGroupRelations === []) {
            return;
        }

        $this->labelResolver->preload(
            LabelEntityType::Key,
            $this->keyIds($keyGroupRelations),
            $this->labelLanguageResolver->getLabelLanguage()
        );
    }

    /**
     * @param KeyGroupRelation[] $keyGroupRelations
     *
     * @return int[]
     */
    private function keyIds(array $keyGroupRelations): array
    {
        return array_map(
            static fn (KeyGroupRelation $keyGroupRelation): int => $keyGroupRelation->getKeyId(),
            $keyGroupRelations
        );
    }
}
