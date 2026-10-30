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

use Codeception\Test\Unit;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\RecycleBin\ItemResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RelocatedItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreContextInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreItemService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class RestoreItemServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testRestoreItemThrowsNotFoundForAMissingItem(): void
    {
        $service = $this->createService(
            itemResolver: $this->makeEmpty(ItemResolverInterface::class, ['getById' => null]),
        );

        $this->expectException(NotFoundException::class);
        $service->restoreItem(999);
    }

    /**
     * @throws Exception
     */
    public function testRestoreItemReturnsTheHydratedRelocatedElements(): void
    {
        $element = $this->makeEmpty(Asset::class);
        $relocatedItem = new RelocatedItem(83, 'asset', '/__restored/image.jpg');

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('getRelocatedElements')->willReturn([$element]);

        $hydrator = $this->createMock(RelocatedItemHydratorInterface::class);
        $hydrator->expects($this->once())->method('hydrate')->with($element)->willReturn($relocatedItem);

        $service = $this->createService(
            itemResolver: $this->createItemResolver(),
            relocatedItemHydrator: $hydrator,
            restoreContext: $restoreContext,
        );

        $this->assertSame([$relocatedItem], $service->restoreItem(42));
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
        ?ItemResolverInterface $itemResolver = null,
        ?RelocatedItemHydratorInterface $relocatedItemHydrator = null,
        ?RestoreContextInterface $restoreContext = null,
        ?SecurityServiceInterface $securityService = null,
    ): RestoreItemService {
        return new RestoreItemService(
            $itemResolver ?? $this->makeEmpty(ItemResolverInterface::class),
            $relocatedItemHydrator ?? $this->makeEmpty(RelocatedItemHydratorInterface::class),
            $restoreContext ?? $this->makeEmpty(RestoreContextInterface::class, ['getRelocatedElements' => []]),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
            $this->makeEmpty(SynchronousProcessingServiceInterface::class),
        );
    }
}
