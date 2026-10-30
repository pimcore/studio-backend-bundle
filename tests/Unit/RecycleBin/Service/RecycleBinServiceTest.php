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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\RecycleBin\ItemResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Event\PreResponse\RelocatedItemEvent;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RecycleBinHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository\RecycleBinRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\JobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinService;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreItemServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class RecycleBinServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testRestoreSingleItemDoesNotCreateJob(): void
    {
        $restoreItemService = $this->makeEmpty(RestoreItemServiceInterface::class, [
            'restoreItem' => Expected::once([]),
        ]);

        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createRestoreJob' => Expected::never(),
        ]);

        $service = $this->createService(
            jobService: $jobService,
            restoreItemService: $restoreItemService,
        );

        $result = $service->restore(new ItemsParameter([42]));

        $this->assertNull($result->getJobRunId());
        $this->assertSame([], $result->getRelocatedItems());
    }

    /**
     * @throws Exception
     */
    public function testRestoreMultipleItemsUsesAsyncPath(): void
    {
        $repository = $this->makeEmpty(RecycleBinRepositoryInterface::class, [
            'getItemIdsSortedByPath' => Expected::once([1, 2]),
        ]);

        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createRestoreJob' => Expected::once(50),
        ]);

        $service = $this->createService(
            repository: $repository,
            jobService: $jobService,
        );

        $result = $service->restore(new ItemsParameter([1, 2]));

        $this->assertSame(50, $result->getJobRunId());
    }

    /**
     * @throws Exception
     */
    public function testRestoreItemException(): void
    {
        $restoreItemService = $this->makeEmpty(RestoreItemServiceInterface::class, [
            'restoreItem' => static function (int $id): array {
                throw new NotFoundException('recycle bin item', $id);
            },
        ]);

        $service = $this->createService(restoreItemService: $restoreItemService);

        $this->expectException(NotFoundException::class);
        $service->restore(new ItemsParameter([999]));
    }

    /**
     * @throws Exception
     */
    public function testRestoreMultipleItems(): void
    {
        $capturedIds = null;

        $repository = $this->makeEmpty(RecycleBinRepositoryInterface::class, [
            'getItemIdsSortedByPath' => function (array $ids) use (&$capturedIds) {
                $capturedIds = $ids;

                // Simulate DB sorting by path: parent IDs come first
                return [5, 10, 15];
            },
        ]);

        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createRestoreJob' => function (array $sortedIds) {
                // Verify the sorted IDs from the repository are passed through
                $this->assertSame([5, 10, 15], $sortedIds);

                return 123;
            },
        ]);

        $service = $this->createService(
            repository: $repository,
            jobService: $jobService,
        );

        $result = $service->restore(new ItemsParameter([15, 5, 10]));

        $this->assertSame(123, $result->getJobRunId());
        // Verify original IDs were passed to the repository
        $this->assertSame([15, 5, 10], $capturedIds);
    }

    /**
     * @throws Exception
     */
    public function testRestoreMultipleItemsPassesAllIdsToRepository(): void
    {
        $capturedIds = null;

        $repository = $this->makeEmpty(RecycleBinRepositoryInterface::class, [
            'getItemIdsSortedByPath' => function (array $ids) use (&$capturedIds) {
                $capturedIds = $ids;

                return $ids;
            },
        ]);

        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createRestoreJob' => 77,
        ]);

        $service = $this->createService(
            repository: $repository,
            jobService: $jobService,
        );

        $items = [1, 2, 3, 4, 5];
        $result = $service->restore(new ItemsParameter($items));

        $this->assertSame(77, $result->getJobRunId());
        $this->assertSame($items, $capturedIds);
    }

    /**
     * @throws Exception
     */
    public function testRestoreSingleItemReturnsTheRelocatedItemsAndDispatchesTheirEvents(): void
    {
        $relocatedItem = new RelocatedItem(83, 'asset', '/__restored/image.jpg');

        $restoreItemService = $this->createMock(RestoreItemServiceInterface::class);
        $restoreItemService->expects($this->once())
            ->method('restoreItem')
            ->with(42, null)
            ->willReturn([$relocatedItem]);

        $dispatchedEvents = [];
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => static function (object $event, string $eventName) use (&$dispatchedEvents): object {
                $dispatchedEvents[$eventName] = $event;

                return $event;
            },
        ]);

        $service = $this->createService(
            eventDispatcher: $eventDispatcher,
            restoreItemService: $restoreItemService,
        );

        $result = $service->restore(new ItemsParameter([42]));

        $this->assertSame([$relocatedItem], $result->getRelocatedItems());
        $this->assertInstanceOf(RelocatedItemEvent::class, $dispatchedEvents[RelocatedItemEvent::EVENT_NAME]);
        $this->assertSame($relocatedItem, $dispatchedEvents[RelocatedItemEvent::EVENT_NAME]->getRelocatedItem());
    }

    /**
     * @throws Exception
     */
    public function testRestoreItemDelegatesForTheCurrentUser(): void
    {
        $restoreItemService = $this->createMock(RestoreItemServiceInterface::class);
        $restoreItemService->expects($this->once())->method('restoreItem')->with(42, null)->willReturn([]);

        $this->createService(restoreItemService: $restoreItemService)->restoreItem(42);
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?RecycleBinRepositoryInterface $repository = null,
        ?JobServiceInterface $jobService = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?RestoreItemServiceInterface $restoreItemService = null,
    ): RecycleBinService {
        return new RecycleBinService(
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterMapperServiceInterface::class),
            $this->makeEmpty(ItemResolverInterface::class),
            $jobService ?? $this->makeEmpty(JobServiceInterface::class),
            $this->makeEmpty(RecycleBinHydratorInterface::class),
            $repository ?? $this->makeEmpty(RecycleBinRepositoryInterface::class),
            $restoreItemService ?? $this->makeEmpty(RestoreItemServiceInterface::class, ['restoreItem' => []]),
        );
    }
}
