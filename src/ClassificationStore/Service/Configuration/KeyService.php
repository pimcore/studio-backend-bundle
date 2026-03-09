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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageParameters;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageResponse;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class KeyService implements KeyServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private KeyRepositoryInterface $keyConfigurationRepository,
        private KeyHydratorInterface $keyHydrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection {
        $listing = $this->keyConfigurationRepository->getListing(
            $this->filterMapper->getFilterParameters($parameters),
            $storeId
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
        $keyConfig = $this->keyConfigurationRepository->create(
            $parameters->getName(),
            $parameters->getStoreId()
        );

        return $this->getHydratedKeyDetail($keyConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function updateKey(int $id, KeyUpdate $parameters): KeyDetail
    {
        $keyConfig = $this->keyConfigurationRepository->update(
            $id,
            $parameters->getName(),
            $parameters->getTitle(),
            $parameters->getDescription(),
            $parameters->getType(),
            $parameters->getDefinition(),
        );

        return $this->getHydratedKeyDetail($keyConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function softDeleteKey(int $id): void
    {
        $this->keyConfigurationRepository->softDelete($id);
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
