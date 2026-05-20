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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\RecycleBin\ItemResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Event\PreResponse\RecycleBinEvent;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\DeleteItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RecycleBinHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository\RecycleBinRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RecycleBin;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\Element\Recyclebin as ElementRecycleBin;
use Pimcore\Model\Element\Recyclebin\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class RecycleBinService implements RecycleBinServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private ItemResolverInterface $itemResolver,
        private JobServiceInterface $jobService,
        private RecycleBinHydratorInterface $hydrator,
        private RecycleBinRepositoryInterface $recycleBinRepository,
        private SynchronousProcessingServiceInterface $synchronousProcessing
    ) {
    }

    public function listRecycleBin(CollectionFilterParameter $parameters): Collection
    {
        $listing = $this->recycleBinRepository->getListing($this->getFilterParameters($parameters));
        $items = $listing->load();
        $list = [];

        foreach ($items as $item) {
            $list[] = $this->getHydratedItem($item);
        }

        return new Collection(
            $listing->count(),
            $list
        );
    }

    /**
     * {@inheritdoc}
     */
    public function restore(ItemsParameter $parameter): ?int
    {
        $items = $parameter->getItems();
        if (count($items) === 1) {
            $this->restoreItem($items[0]);

            return null;
        }

        $sortedIds = $this->recycleBinRepository->getItemIdsSortedByPath($items);

        return $this->jobService->createRestoreJob($sortedIds);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ItemsParameter $parameter): ?int
    {
        $items = $parameter->getItems();
        if (count($items) === 1) {
            $this->deleteItem($items[0]);

            return null;
        }

        return $this->jobService->createJob(
            Jobs::RECYCLE_BIN_DELETE->value,
            JobSteps::DELETE_ITEMS->value,
            DeleteItemsMessage::class,
            $items
        );
    }

    public function flushRecycleBin(): void
    {
        $bin = new ElementRecycleBin();
        $bin->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function restoreItem(int $id): void
    {
        $syncProcessingEnabled = $this->synchronousProcessing->isEnabled();
        $item = $this->itemResolver->getById($id);
        if (!$item instanceof Item) {
            throw new NotFoundException('recycle bin item', $id);
        }

        try {
            $this->synchronousProcessing->enable();
            $item->restore();
        } catch (Exception $e) {
            $errorKey = HttpResponseErrorKeys::RECYCLE_BIN_RESTORE;
            if (str_contains($e->getMessage(), 'ParentID is mandatory and can´t be null')) {
                $errorKey = HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT;
            }
            throw new EnvironmentException($e->getMessage(), $errorKey->value, $e);
        } finally {
            $syncProcessingEnabled ? $this->synchronousProcessing->enable() :
            $this->synchronousProcessing->disable();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(int $id): void
    {
        $item = $this->itemResolver->getById($id);
        if (!$item instanceof Item) {
            throw new NotFoundException('recycle bin item', $id);
        }

        try {
            $item->delete();
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }
    }

    private function getHydratedItem(Item $item): RecycleBin
    {
        $entry = $this->hydrator->hydrate($item);
        $this->eventDispatcher->dispatch(
            new RecycleBinEvent($entry),
            RecycleBinEvent::EVENT_NAME
        );

        return $entry;
    }

    private function getFilterParameters(CollectionFilterParameter $parameters): FilterParameter
    {
        $filterParameters = new FilterParameter();
        if ($parameters->getFilters()) {
            $filterParameters = $this->filterMapper->map($parameters);
        }

        return $filterParameters;
    }
}
