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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\PageSnippetService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Controller\Config\ControllerDataProvider;
use Pimcore\Model\Document\Page;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class PageSnippetServiceTest extends Unit
{
    public function testTargetSnippetIsAuthorizedBeforeSourcePath(): void
    {
        $user = new User();
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($user);

        $documentService = $this->createMock(DocumentServiceInterface::class);
        $documentService->method('getDocumentElement')
            ->willThrowException(new ForbiddenException('target not viewable'));
        $documentService->expects($this->never())->method('getDocumentElementByPath');

        $service = new PageSnippetService(
            $this->createMock(ControllerDataProvider::class),
            $documentService,
            $this->createMock(EventDispatcherInterface::class),
            $securityService,
        );

        $this->expectException(ForbiddenException::class);
        $service->setMainDocument(5, '/does/not/exist');
    }

    public function testMissingSourcePathStillReportedWhenTargetIsViewable(): void
    {
        $user = new User();
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($user);

        $documentService = $this->createMock(DocumentServiceInterface::class);
        $documentService->method('getDocumentElement')
            ->willReturn($this->createMock(Page::class));
        $documentService->method('getDocumentElementByPath')
            ->willThrowException(new NotFoundException('document', 0));

        $service = new PageSnippetService(
            $this->createMock(ControllerDataProvider::class),
            $documentService,
            $this->createMock(EventDispatcherInterface::class),
            $securityService,
        );

        $this->expectException(NotFoundException::class);
        $service->setMainDocument(5, '/does/not/exist');
    }
}
