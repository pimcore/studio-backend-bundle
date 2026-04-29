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
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\RecycleBin\ItemResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RecycleBinHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository\RecycleBinRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\JobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinService;
use Pimcore\Model\Element\Recyclebin\Item;
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

        $this->assertNull($result);
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

        $this->assertSame(50, $result);
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

        $this->assertSame(123, $result);
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

        $this->assertSame(77, $result);
        $this->assertSame($items, $capturedIds);
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?RecycleBinRepositoryInterface $repository = null,
        ?JobServiceInterface $jobService = null,
        ?ItemResolverInterface $itemResolver = null,
    ): RecycleBinService {
        return new RecycleBinService(
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterMapperServiceInterface::class),
            $itemResolver ?? $this->makeEmpty(ItemResolverInterface::class),
            $jobService ?? $this->makeEmpty(JobServiceInterface::class),
            $this->makeEmpty(RecycleBinHydratorInterface::class),
            $repository ?? $this->makeEmpty(RecycleBinRepositoryInterface::class),
            $this->makeEmpty(SynchronousProcessingServiceInterface::class),
        );
    }
}
