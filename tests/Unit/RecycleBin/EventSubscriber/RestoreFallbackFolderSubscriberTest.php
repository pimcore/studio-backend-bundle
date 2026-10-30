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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\EventSubscriber;

use Codeception\Test\Unit;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\EventSubscriber\RestoreFallbackFolderSubscriber;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\FallbackFolderServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RestoreContextInterface;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;
use function array_keys;

/**
 * @internal
 */
final class RestoreFallbackFolderSubscriberTest extends Unit
{
    public function testSubscribesToThePreSaveEventsOfAllElementTypes(): void
    {
        $this->assertSame(
            [
                AssetEvents::PRE_ADD,
                AssetEvents::PRE_UPDATE,
                DocumentEvents::PRE_ADD,
                DocumentEvents::PRE_UPDATE,
                DataObjectEvents::PRE_ADD,
                DataObjectEvents::PRE_UPDATE,
            ],
            array_keys(RestoreFallbackFolderSubscriber::getSubscribedEvents())
        );
    }

    /**
     * @throws Exception
     */
    #[DataProvider('saveWithoutRestoreArgumentProvider')]
    public function testIgnoresSavesOutsideARecycleBinRestore(array $arguments): void
    {
        $fallbackFolderService = $this->createMock(FallbackFolderServiceInterface::class);
        $fallbackFolderService->expects($this->never())->method('hasMissingParent');
        $fallbackFolderService->expects($this->never())->method('moveToFallbackFolder');

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('getUser')->willReturn($this->makeEmpty(UserInterface::class));

        $subscriber = new RestoreFallbackFolderSubscriber($fallbackFolderService, $restoreContext);
        $subscriber->onPreSave($this->createEvent($arguments));
    }

    public static function saveWithoutRestoreArgumentProvider(): array
    {
        return [
            'regular save' => [[]],
            'restore argument false' => [['isRecycleBinRestore' => false]],
        ];
    }

    /**
     * @throws Exception
     */
    public function testIgnoresCoreRestoresThatTheStudioDidNotStart(): void
    {
        $fallbackFolderService = $this->createMock(FallbackFolderServiceInterface::class);
        $fallbackFolderService->expects($this->never())->method('hasMissingParent');
        $fallbackFolderService->expects($this->never())->method('moveToFallbackFolder');

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('getUser')->willReturn(null);

        $subscriber = new RestoreFallbackFolderSubscriber($fallbackFolderService, $restoreContext);
        $subscriber->onPreSave($this->createEvent(['isRecycleBinRestore' => true]));
    }

    /**
     * @throws Exception
     */
    public function testLeavesAnElementWithAnExistingParentAlone(): void
    {
        $fallbackFolderService = $this->createMock(FallbackFolderServiceInterface::class);
        $fallbackFolderService->method('hasMissingParent')->willReturn(false);
        $fallbackFolderService->expects($this->never())->method('moveToFallbackFolder');

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('getUser')->willReturn($this->makeEmpty(UserInterface::class));
        $restoreContext->expects($this->never())->method('addRelocatedElement');

        $subscriber = new RestoreFallbackFolderSubscriber($fallbackFolderService, $restoreContext);
        $subscriber->onPreSave($this->createEvent(['isRecycleBinRestore' => true]));
    }

    /**
     * @throws Exception
     */
    public function testMovesAnElementWithAMissingParentIntoTheFallbackFolder(): void
    {
        $user = $this->makeEmpty(UserInterface::class);
        $element = $this->makeEmpty(ElementInterface::class);

        $fallbackFolderService = $this->createMock(FallbackFolderServiceInterface::class);
        $fallbackFolderService->expects($this->once())->method('hasMissingParent')->with($element)->willReturn(true);
        $fallbackFolderService->expects($this->once())->method('moveToFallbackFolder')->with($element, $user);

        $restoreContext = $this->createMock(RestoreContextInterface::class);
        $restoreContext->method('getUser')->willReturn($user);
        $restoreContext->expects($this->once())->method('addRelocatedElement')->with($element);

        $subscriber = new RestoreFallbackFolderSubscriber($fallbackFolderService, $restoreContext);
        $subscriber->onPreSave($this->createEvent(['isRecycleBinRestore' => true], $element));
    }

    /**
     * @throws Exception
     */
    private function createEvent(array $arguments, ?ElementInterface $element = null): ElementEventInterface
    {
        return $this->makeEmpty(ElementEventInterface::class, [
            'hasArgument' => static fn (string $key): bool => array_key_exists($key, $arguments),
            'getArgument' => static fn (string $key): mixed => $arguments[$key],
            'getElement' => $element ?? $this->makeEmpty(ElementInterface::class),
        ]);
    }
}
