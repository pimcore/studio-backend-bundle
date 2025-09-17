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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ImageService;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
#[CoversClass(ImageService::class)]
#[UsesClass(ForbiddenException::class)]
#[UsesClass(AbstractApiException::class)]
final class ImageServiceTest extends TestCase
{
    public function testNonAdminCanNotEditAdminUser(): void
    {
        $userMock = $this->createMock(UserInterface::class);
        $userMock->method('isAdmin')->willReturn(true);

        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(false);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userMock);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $securityServiceMock->method('getCurrentUser')->willReturn($currentUserMock);

        $assetResolver = $this->createMock(AssetResolverInterface::class);

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only admin users are allowed to modify admin users');
        $imageUploadService->uploadUserImage($this->createMock(UploadedFile::class), 1);
    }

    public function testWrongFileType(): void
    {
        $userMock = $this->createMock(UserInterface::class);
        $userMock->method('isAdmin')->willReturn(true);

        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(true);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userMock);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $securityServiceMock->method('getCurrentUser')->willReturn($currentUserMock);

        $assetResolver = $this->createMock(AssetResolverInterface::class);
        $assetResolver->method('getTypeFromMimeMapping')->willReturn('document');

        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->method('getMimeType')->willReturn('application/pdf');
        $fileMock->method('getFilename')->willReturn('test.pdf');

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only images are allowed');
        $imageUploadService->uploadUserImage($fileMock, 1);
    }

    public function testSetImageOfUserIsCalled(): void
    {
        $userMock = $this->createMock(UserInterface::class);
        $userMock->method('isAdmin')->willReturn(true);
        $userMock->expects($this->once())
            ->method('setImage')
            ->with($this->callback(function (string $path) {
                $this->assertSame('/tmp/test.png', $path);

                return true;
            }));

        $currentUserMock = $this->createMock(UserInterface::class);
        $currentUserMock->method('isAdmin')->willReturn(true);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userMock);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);
        $securityServiceMock->method('getCurrentUser')->willReturn($currentUserMock);

        $assetResolver = $this->createMock(AssetResolverInterface::class);
        $assetResolver->method('getTypeFromMimeMapping')->willReturn('image');

        $fileMock = $this->createMock(UploadedFile::class);
        $fileMock->method('getMimeType')->willReturn('image/png');
        $fileMock->method('getFilename')->willReturn('test.png');
        $fileMock->method('getPathname')->willReturn('/tmp/test.png');

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $imageUploadService->uploadUserImage($fileMock, 1);
    }

    public function testGetImageAsStreamedResponse(): void
    {
        $userMock = $this->createMock(UserInterface::class);
        $resource = fopen('php://memory', 'r');
        $userMock->method('getImage')->willReturn($resource);

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock->method('getUserById')->willReturn($userMock);

        $securityServiceMock = $this->createMock(SecurityServiceInterface::class);

        $assetResolverMock = $this->createMock(AssetResolverInterface::class);

        $imageService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolverMock);

        $result = $imageService->getImageFromUserAsStreamedResponse(1);

        $this->assertInstanceOf(StreamedResponse::class, $result);
    }
}
