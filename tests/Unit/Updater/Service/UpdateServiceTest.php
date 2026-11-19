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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Updater\Service;

use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface as DataObjectDataService;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface as DocumentDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\AdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateService;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

final class UpdateServiceTest extends Unit
{
    private MockObject&AdapterLoaderInterface $adapterLoader;

    private MockObject&DocumentDataService $documentDataService;

    private MockObject&SecurityServiceInterface $securityService;

    private MockObject&ServiceResolverInterface $serviceResolver;

    private MockObject&ElementSaveServiceInterface $saveService;

    private MockObject&UserInterface $user;

    private UpdateService $service;

    protected function setUp(): void
    {
        $this->adapterLoader = $this->createMock(AdapterLoaderInterface::class);
        $this->documentDataService = $this->createMock(DocumentDataService::class);
        $this->securityService = $this->createMock(SecurityServiceInterface::class);
        $this->serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $this->saveService = $this->createMock(ElementSaveServiceInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->securityService->method('getCurrentUser')->willReturn($this->user);

        $this->service = new UpdateService(
            $this->adapterLoader,
            $this->createMock(DataObjectDataService::class),
            $this->documentDataService,
            $this->securityService,
            $this->serviceResolver,
            $this->createMock(ElementIndexServiceInterface::class),
            $this->saveService,
        );
    }

    public function testDocumentUpdateWithoutSavePermissionIsDenied(): void
    {
        $document = $this->documentMock(2, 1);
        $this->resolve([2 => $document]);
        $this->securityService->method('hasElementPermission')
            ->willThrowException(new ForbiddenException('denied'));

        $this->adapterLoader->expects($this->never())->method('loadAdapters');
        $this->documentDataService->expects($this->never())->method('updateDocumentData');
        $this->saveService->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);
        $this->service->update('document', 2, ['key' => 'x']);
    }

    public function testDocumentUpdateWithSavePermissionIsPersisted(): void
    {
        $document = $this->documentMock(2, 1);
        $this->resolve([2 => $document]);
        $this->securityService->expects($this->atLeastOnce())->method('hasElementPermission')
            ->with($document, $this->user, 'save');
        $this->adapterLoader->method('loadAdapters')->willReturn([]);
        $this->saveService->expects($this->once())->method('save');

        $this->service->update('document', 2, ['key' => 'x', 'task' => 'save']);
    }

    public function testDocumentPublishDoesNotRequireSaveInUpdateService(): void
    {
        $document = $this->documentMock(2, 1);
        $this->resolve([2 => $document]);
        $this->securityService->expects($this->never())->method('hasElementPermission');
        $this->adapterLoader->method('loadAdapters')->willReturn([]);
        $this->saveService->expects($this->once())->method('save');

        $this->service->update('document', 2, ['task' => 'publish']);
    }

    public function testDocumentMoveRequiresCreatePermissionOnDestination(): void
    {
        $document = $this->documentMock(2, 1);
        $target = $this->documentMock(5, 1);
        $this->resolve([2 => $document, 5 => $target]);
        $this->securityService->method('hasElementPermission')
            ->willReturnCallback(static function (ElementInterface $element, $user, string $permission): void {
                if ($permission === 'create') {
                    throw new ForbiddenException('denied');
                }
            });

        $this->adapterLoader->expects($this->never())->method('loadAdapters');
        $this->saveService->expects($this->never())->method('save');

        $this->expectException(ForbiddenException::class);
        $this->service->update('document', 2, ['parentId' => 5, 'task' => 'save']);
    }

    public function testDocumentWithUnchangedParentNeedsNoCreatePermission(): void
    {
        $document = $this->documentMock(2, 1);
        $this->resolve([2 => $document]);
        $permissions = [];
        $this->securityService->method('hasElementPermission')
            ->willReturnCallback(static function ($element, $user, string $permission) use (&$permissions): void {
                $permissions[] = $permission;
            });
        $this->adapterLoader->method('loadAdapters')->willReturn([]);
        $this->saveService->expects($this->once())->method('save');

        $this->service->update('document', 2, ['parentId' => 1, 'task' => 'save']);

        $this->assertContains('save', $permissions);
        $this->assertNotContains('create', $permissions);
    }

    public function testConcreteStillRequiresSavePermission(): void
    {
        $object = $this->createMock(Concrete::class);
        $this->resolve([3 => $object]);
        $this->securityService->expects($this->once())->method('hasElementPermission')
            ->with($object, $this->user, 'save');
        $this->adapterLoader->method('loadAdapters')->willReturn([]);

        $this->service->update('data-object', 3, ['task' => 'save']);
    }

    public function testAssetStillRequiresPublishPermission(): void
    {
        $asset = $this->createMock(Asset::class);
        $this->resolve([4 => $asset]);
        $this->securityService->expects($this->once())->method('hasElementPermission')
            ->with($asset, $this->user, 'publish');
        $this->adapterLoader->method('loadAdapters')->willReturn([]);

        $this->service->update('asset', 4, []);
    }

    private function documentMock(int $id, int $parentId): MockObject&Page
    {
        $document = $this->createMock(Page::class);
        $document->method('getId')->willReturn($id);
        $document->method('getParentId')->willReturn($parentId);

        return $document;
    }

    private function resolve(array $elements): void
    {
        $this->serviceResolver->method('getElementById')
            ->willReturnCallback(static fn (string $type, int $id) => $elements[$id] ?? null);
    }
}
