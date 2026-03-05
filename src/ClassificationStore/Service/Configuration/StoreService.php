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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\StoreDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration\StoreTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\StoreHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreUpdate;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class StoreService implements StoreServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private StoreRepositoryInterface $storeConfigurationRepository,
        private StoreHydratorInterface $storeHydrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createStore(StoreCreate $parameters): StoreDetail
    {
        $storeConfig = $this->storeConfigurationRepository->create($parameters->getName());

        return $this->getHydratedStoreDetail($storeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStore(int $id, StoreUpdate $parameters): StoreDetail
    {
        $storeConfig = $this->storeConfigurationRepository->update(
            $id,
            $parameters->getName(),
            $parameters->getDescription(),
        );

        return $this->getHydratedStoreDetail($storeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreTree(): array
    {
        $stores = $this->storeConfigurationRepository->listStores();
        $treeNodes = [];

        foreach ($stores as $store) {
            $treeNode = $this->storeHydrator->hydrateStoreTreeNode($store);
            $this->eventDispatcher->dispatch(
                new StoreTreeNodeEvent($treeNode),
                StoreTreeNodeEvent::EVENT_NAME
            );
            $treeNodes[] = $treeNode;
        }

        return $treeNodes;
    }

    private function getHydratedStoreDetail(StoreConfig $storeConfig): StoreDetail
    {
        $storeDetail = $this->storeHydrator->hydrateStoreDetail($storeConfig);
        $this->eventDispatcher->dispatch(
            new StoreDetailEvent($storeDetail),
            StoreDetailEvent::EVENT_NAME
        );

        return $storeDetail;
    }
}
