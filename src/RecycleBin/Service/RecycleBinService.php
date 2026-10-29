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
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Event\PreResponse\RelocatedItemEvent;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\DeleteItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RecycleBinHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RelocatedItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Model\RestoreResult;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository\RecycleBinRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RecycleBin;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Recyclebin as ElementRecycleBin;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function count;
use function str_contains;

/**
 * @internal
 */
final readonly class RecycleBinService implements RecycleBinServiceInterface
{
    private const array MISSING_PARENT_MESSAGES = [
        'ParentID is mandatory and can´t be null',
        'ParentID not found.',
    ];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private ItemResolverInterface $itemResolver,
        private JobServiceInterface $jobService,
        private RecycleBinHydratorInterface $hydrator,
        private RecycleBinRepositoryInterface $recycleBinRepository,
        private SynchronousProcessingServiceInterface $synchronousProcessing,
        private RelocatedItemHydratorInterface $relocatedItemHydrator,
        private RestoreContextInterface $restoreContext,
        private SecurityServiceInterface $securityService,
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
    public function restore(ItemsParameter $parameter): RestoreResult
    {
        $items = $parameter->getItems();
        if (count($items) === 1) {
            $relocatedItems = $this->restoreItem($items[0]);
            foreach ($relocatedItems as $relocatedItem) {
                $this->eventDispatcher->dispatch(
                    new RelocatedItemEvent($relocatedItem),
                    RelocatedItemEvent::EVENT_NAME
                );
            }

            return new RestoreResult(relocatedItems: $relocatedItems);
        }

        $sortedIds = $this->recycleBinRepository->getItemIdsSortedByPath($items);

        return new RestoreResult(jobRunId: $this->jobService->createRestoreJob($sortedIds));
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
    public function restoreItem(int $id, ?UserInterface $user = null): array
    {
        $syncProcessingEnabled = $this->synchronousProcessing->isEnabled();
        $item = $this->itemResolver->getById($id);
        if (!$item instanceof Item) {
            throw new NotFoundException('recycle bin item', $id);
        }

        $user ??= $this->securityService->getCurrentUser();

        try {
            $this->synchronousProcessing->enable();
            $this->restoreContext->start($user);
            $item->restore();
            $relocatedElements = $this->restoreContext->getRelocatedElements();
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage(), $this->getRestoreErrorKey($e)->value, $e);
        } finally {
            $this->restoreContext->clear();
            $syncProcessingEnabled ? $this->synchronousProcessing->enable() :
            $this->synchronousProcessing->disable();
        }

        return array_map(
            fn (ElementInterface $element): RelocatedItem => $this->relocatedItemHydrator->hydrate($element),
            $relocatedElements
        );
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

    private function getRestoreErrorKey(Exception $exception): HttpResponseErrorKeys
    {
        $missingParentKey = HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT;
        if ($exception instanceof EnvironmentException && $exception->getErrorKey() === $missingParentKey->value) {
            return $missingParentKey;
        }

        foreach (self::MISSING_PARENT_MESSAGES as $message) {
            if (str_contains($exception->getMessage(), $message)) {
                return $missingParentKey;
            }
        }

        return HttpResponseErrorKeys::RECYCLE_BIN_RESTORE;
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
