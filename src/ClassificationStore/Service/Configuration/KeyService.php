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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\GetPageEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\KeyDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\KeyCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageParameters;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageResponse;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexRemapTriggerInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\OptionsProviderDefaultsTrait;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class KeyService implements KeyServiceInterface
{
    use OptionsProviderDefaultsTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private KeyRelationResolverInterface $keyRelationResolver,
        private KeyRepositoryInterface $keyConfigurationRepository,
        private KeyHydratorInterface $keyHydrator,
        private SearchIndexRemapTriggerInterface $searchIndexRemapTrigger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(
        KeyCollectionParameters $parameters,
        int $storeId,
    ): Collection {
        $listing = $this->keyConfigurationRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters),
            $storeId,
            $parameters->isDisabledOnly()
        );
        $configs = $listing->load();
        $items = [];

        foreach ($configs as $config) {
            $items[] = $this->getHydratedKeyDetail($config);
        }

        return new Collection(
            $listing->getTotalCount(),
            $items
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createKey(KeyCreate $parameters): KeyDetail
    {
        try {
            $keyConfig = $this->keyConfigurationRepository->create(
                $parameters->getName(),
                $parameters->getStoreId(),
                $parameters->getTitle()
            );
        } finally {
            $this->keyRelationResolver->clear();
        }

        return $this->getHydratedKeyDetail($keyConfig);
    }

    /**
     * {@inheritdoc}
     *
     * The search index flag lives in the `definition` blob, so only an update that carries one can
     * change it. The blob it is compared against is the one the write answers with rather than one
     * read here: the repository holds the key row for its write and reports the definition that row
     * carried when the lock was taken, which is the only value a comparison can be made against
     * without a competing writer of the same key invalidating it in between
     * ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyWriteResult}).
     */
    public function updateKey(int $id, KeyUpdate $parameters): KeyDetail
    {
        $definition = $parameters->getDefinition();

        if ($definition !== null) {
            $parameters = $parameters->withDefinition($this->applyOptionsProviderDefaults($definition));
        }

        try {
            $write = $this->keyConfigurationRepository->update($id, $parameters);
            $keyConfig = $write->getConfig();

            if ($definition !== null) {
                $this->searchIndexRemapTrigger->noteFlagChange(
                    $keyConfig->getStoreId(),
                    $write->getDefinitionBefore(),
                    $keyConfig->getDefinition()
                );
            }
        } finally {
            $this->keyRelationResolver->clear();
            $this->searchIndexRemapTrigger->flush();
        }

        return $this->getHydratedKeyDetail($keyConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function softDeleteKey(int $id): void
    {
        try {
            $this->keyConfigurationRepository->softDelete($id);
        } finally {
            $this->keyRelationResolver->clear();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restoreKey(int $id): KeyDetail
    {
        try {
            $keyConfig = $this->keyConfigurationRepository->restore($id);
        } finally {
            $this->keyRelationResolver->clear();
        }

        return $this->getHydratedKeyDetail($keyConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(GetPageParameters $parameters): GetPageResponse
    {
        $page = $this->keyConfigurationRepository->getPageForId(
            $parameters->getTable(),
            $parameters->getId(),
            $parameters->getStoreId(),
            $parameters->getPageSize(),
            $parameters->getSortKey(),
            $parameters->getSortDir(),
            $parameters->isDisabledOnly(),
        );
        $response = $this->keyHydrator->hydrateGetPageResponse($page);
        $this->eventDispatcher->dispatch(
            new GetPageEvent($response),
            GetPageEvent::EVENT_NAME
        );

        return $response;
    }

    private function getHydratedKeyDetail(KeyConfig $keyConfig): KeyDetail
    {
        $keyDetail = $this->keyHydrator->hydrateKeyDetail($keyConfig);
        $this->eventDispatcher->dispatch(
            new KeyDetailEvent($keyDetail),
            KeyDetailEvent::EVENT_NAME
        );

        return $keyDetail;
    }
}
