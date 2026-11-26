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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\UserInterface;

final class ElementSaveServiceTest extends Unit
{
    private MockObject&SecurityServiceInterface $securityService;

    private MockObject&UserInterface $user;

    private ElementSaveService $service;

    protected function setUp(): void
    {
        $this->securityService = $this->createMock(SecurityServiceInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->user->method('getId')->willReturn(7);

        $this->service = new ElementSaveService(
            $this->createMock(SynchronousProcessingServiceInterface::class),
            $this->securityService
        );
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function plainSaveTasks(): array
    {
        return [
            'no task' => [null],
            'save task' => [ElementSaveTasks::SAVE->value],
        ];
    }

    /**
     * @dataProvider plainSaveTasks
     */
    public function testPublishedObjectWithoutPublishPermissionIsOnlySavedAsVersion(?string $task): void
    {
        $object = $this->objectMock(published: true);
        $this->denyPublish();

        $object->expects($this->never())->method('save');
        $object->expects($this->once())->method('saveVersion');

        $this->service->save($object, $this->user, $task);
    }

    /**
     * @dataProvider plainSaveTasks
     */
    public function testPublishedObjectWithPublishPermissionIsSavedLive(?string $task): void
    {
        $object = $this->objectMock(published: true);
        $this->securityService->expects($this->once())->method('hasElementPermission')
            ->with($object, $this->user, ElementPermissions::PUBLISH_PERMISSION);

        $object->expects($this->once())->method('save');
        $object->expects($this->never())->method('saveVersion');

        $this->service->save($object, $this->user, $task);
    }

    /**
     * @dataProvider plainSaveTasks
     */
    public function testUnpublishedObjectWithoutPublishPermissionIsSaved(?string $task): void
    {
        $object = $this->objectMock(published: false);
        $this->denyPublish();

        $object->expects($this->once())->method('save');
        $object->expects($this->never())->method('saveVersion');

        $this->service->save($object, $this->user, $task);
    }

    public function testPublishTaskStillRequiresPublishPermission(): void
    {
        $object = $this->objectMock(published: true);
        $this->denyPublish();

        $object->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);
        $this->service->save($object, $this->user, ElementSaveTasks::PUBLISH->value);
    }

    /**
     * @dataProvider plainSaveTasks
     */
    public function testPublishedPageWithoutPublishPermissionIsOnlySavedAsVersion(?string $task): void
    {
        $page = $this->pageMock(published: true);
        $this->denyPublish();

        $page->expects($this->never())->method('save');
        $page->expects($this->once())->method('saveVersion');

        $this->service->save($page, $this->user, $task);
    }

    /**
     * @dataProvider plainSaveTasks
     */
    public function testPublishedPageWithPublishPermissionIsSavedLive(?string $task): void
    {
        $page = $this->pageMock(published: true);
        $this->securityService->expects($this->once())->method('hasElementPermission')
            ->with($page, $this->user, ElementPermissions::PUBLISH_PERMISSION);

        $page->expects($this->once())->method('save');
        $page->expects($this->never())->method('saveVersion');

        $this->service->save($page, $this->user, $task);
    }

    /**
     * @dataProvider plainSaveTasks
     */
    public function testUnpublishedPageWithoutPublishPermissionIsSaved(?string $task): void
    {
        $page = $this->pageMock(published: false);
        $this->denyPublish();

        $page->expects($this->once())->method('save');
        $page->expects($this->never())->method('saveVersion');

        $this->service->save($page, $this->user, $task);
    }

    public function testDocumentWithoutVersionsIsSavedAsBefore(): void
    {
        // only page snippets can hold a draft version, so other document types keep their plain save
        $link = $this->createMock(Link::class);
        $link->method('isPublished')->willReturn(true);
        $this->denyPublish();

        $link->expects($this->once())->method('save');

        $this->service->save($link, $this->user);
    }

    private function denyPublish(): void
    {
        $this->securityService->method('hasElementPermission')
            ->willThrowException(new ForbiddenException('denied'));
    }

    private function objectMock(bool $published): MockObject&Concrete
    {
        $object = $this->createMock(Concrete::class);
        $object->method('isPublished')->willReturn($published);

        return $object;
    }

    private function pageMock(bool $published): MockObject&Page
    {
        $page = $this->createMock(Page::class);
        $page->method('isPublished')->willReturn($published);

        return $page;
    }
}
