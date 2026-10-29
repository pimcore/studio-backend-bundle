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
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\RecycleBin\ItemResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Event\PreResponse\RelocatedItemEvent;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RecycleBinHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RelocatedItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository\RecycleBinRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\JobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinService;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreContextInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\UserInterface;
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
        $itemResolver = $this->makeEmpty(ItemResolverInterface::class, [
            'getById' => $this->make(Item::class, [
                'restore' => null,
            ]),
        ]);

        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createRestoreJob' => Expected::never(),
        ]);

        $service = $this->createService(
            jobService: $jobService,
            itemResolver: $itemResolver,
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
        $itemResolver = $this->makeEmpty(ItemResolverInterface::class, [
            'getById' => null,
        ]);

        $service = $this->createService(itemResolver: $itemResolver);

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
        $element = $this->makeEmpty(Asset::class);
        $relocatedItem = new RelocatedItem(83, 'asset', '/__restored/image.jpg');

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('getRelocatedElements')->willReturn([$element]);

        $hydrator = $this->createMock(RelocatedItemHydratorInterface::class);
        $hydrator->expects($this->once())->method('hydrate')->with($element)->willReturn($relocatedItem);

        $dispatchedEvents = [];
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => static function (object $event, string $eventName) use (&$dispatchedEvents): object {
                $dispatchedEvents[$eventName] = $event;

                return $event;
            },
        ]);

        $service = $this->createService(
            itemResolver: $this->createItemResolver(),
            eventDispatcher: $eventDispatcher,
            relocatedItemHydrator: $hydrator,
            restoreContext: $restoreContext,
        );

        $result = $service->restore(new ItemsParameter([42]));

        $this->assertSame([$relocatedItem], $result->getRelocatedItems());
        $this->assertInstanceOf(RelocatedItemEvent::class, $dispatchedEvents[RelocatedItemEvent::EVENT_NAME]);
        $this->assertSame($relocatedItem, $dispatchedEvents[RelocatedItemEvent::EVENT_NAME]->getRelocatedItem());
    }

    /**
     * @throws Exception
     */
    public function testRestoreItemRunsTheRestoreInTheContextOfTheGivenUser(): void
    {
        $user = $this->makeEmpty(UserInterface::class);
        $calls = [];

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('start')->willReturnCallback(
            function (UserInterface $contextUser) use (&$calls, $user): void {
                $this->assertSame($user, $contextUser);
                $calls[] = 'start';
            }
        );
        $restoreContext->method('getRelocatedElements')->willReturn([]);
        $restoreContext->method('clear')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'clear';
        });

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->never())->method('getCurrentUser');

        $service = $this->createService(
            itemResolver: $this->createItemResolver(static function () use (&$calls): void {
                $calls[] = 'restore';
            }),
            restoreContext: $restoreContext,
            securityService: $securityService,
        );

        $this->assertSame([], $service->restoreItem(42, $user));
        $this->assertSame(['start', 'restore', 'clear'], $calls);
    }

    /**
     * @throws Exception
     */
    public function testRestoreItemUsesTheCurrentUserByDefault(): void
    {
        $user = $this->makeEmpty(UserInterface::class);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->once())->method('getCurrentUser')->willReturn($user);

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->expects($this->once())->method('start')->with($user);
        $restoreContext->method('getRelocatedElements')->willReturn([]);

        $service = $this->createService(
            itemResolver: $this->createItemResolver(),
            restoreContext: $restoreContext,
            securityService: $securityService,
        );

        $service->restoreItem(42);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('restoreFailureProvider')]
    public function testRestoreItemMapsTheFailureToAnErrorKeyAndClearsTheContext(
        Exception $failure,
        HttpResponseErrorKeys $expectedKey
    ): void {
        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->expects($this->once())->method('clear');

        $service = $this->createService(
            itemResolver: $this->createItemResolver(static function () use ($failure): void {
                throw $failure;
            }),
            restoreContext: $restoreContext,
        );

        try {
            $service->restoreItem(42, $this->makeEmpty(UserInterface::class));
            $this->fail('EnvironmentException expected');
        } catch (EnvironmentException $exception) {
            $this->assertSame($expectedKey->value, $exception->getErrorKey());
            $this->assertSame($failure, $exception->getPrevious());
        }
    }

    public static function restoreFailureProvider(): array
    {
        $missingParent = HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT;

        return [
            'data object without parent id' => [
                new Exception('ParentID is mandatory and can´t be null. If you want to add the element ...'),
                $missingParent,
            ],
            'asset or document with a deleted parent' => [new Exception('ParentID not found.'), $missingParent],
            'fallback folder not allowed' => [
                new EnvironmentException('Missing publish permission', $missingParent->value),
                $missingParent,
            ],
            'other api failure' => [
                new EnvironmentException('Something else'),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE,
            ],
            'other failure' => [
                new Exception('Not sufficient permissions'),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE,
            ],
        ];
    }

    /**
     * @throws Exception
     */
    private function createItemResolver(?callable $restore = null): ItemResolverInterface
    {
        return $this->makeEmpty(ItemResolverInterface::class, [
            'getById' => $this->make(Item::class, [
                'restore' => $restore ?? static function (): void {
                },
            ]),
        ]);
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?RecycleBinRepositoryInterface $repository = null,
        ?JobServiceInterface $jobService = null,
        ?ItemResolverInterface $itemResolver = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?RelocatedItemHydratorInterface $relocatedItemHydrator = null,
        ?RestoreContextInterface $restoreContext = null,
        ?SecurityServiceInterface $securityService = null,
    ): RecycleBinService {
        return new RecycleBinService(
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterMapperServiceInterface::class),
            $itemResolver ?? $this->makeEmpty(ItemResolverInterface::class),
            $jobService ?? $this->makeEmpty(JobServiceInterface::class),
            $this->makeEmpty(RecycleBinHydratorInterface::class),
            $repository ?? $this->makeEmpty(RecycleBinRepositoryInterface::class),
            $this->makeEmpty(SynchronousProcessingServiceInterface::class),
            $relocatedItemHydrator ?? $this->makeEmpty(RelocatedItemHydratorInterface::class),
            $restoreContext ?? $this->makeEmpty(RestoreContextInterface::class, ['getRelocatedElements' => []]),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
        );
    }
}
