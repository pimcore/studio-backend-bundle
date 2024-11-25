<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ImageService;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
final class ImageServiceTest extends Unit
{
    public function testNonAdminCanNotEditAdminUser(): void
    {
        $userMock = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);

        $currentUserMock = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => false,
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userMock,
        ]);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $currentUserMock,
        ]);

        $assetResolver = $this->makeEmpty(AssetResolverInterface::class);

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You are not allowed to upload an image for an admin user');
        $imageUploadService->uploadUserImage($this->makeEmpty(UploadedFile::class), 1);
    }

    public function testWrongFileType(): void
    {
        $userMock = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);

        $currentUserMock = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userMock,
        ]);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $currentUserMock,
        ]);

        $assetResolver = $this->makeEmpty(AssetResolverInterface::class, [
            'getTypeFromMimeMapping' => 'document',
        ]);

        $fileMock = $this->makeEmpty(UploadedFile::class, [
            'getMimeType' => 'application/pdf',
            'getFilename' => 'test.pdf',
        ]);

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Only images are allowed');
        $imageUploadService->uploadUserImage($fileMock, 1);
    }

    public function testSetImageOfUserIsCalled(): void
    {
        $userMock = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
            'setImage' => Expected::once(function (string $path) {
                $this->assertSame('/tmp/test.png', $path);
            }),
        ]);

        $currentUserMock = $this->makeEmpty(UserInterface::class, [
            'isAdmin' => true,
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userMock,
        ]);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $currentUserMock,
        ]);

        $assetResolver = $this->makeEmpty(AssetResolverInterface::class, [
            'getTypeFromMimeMapping' => 'image',
        ]);

        $fileMock = $this->makeEmpty(UploadedFile::class, [
            'getMimeType' => 'image/png',
            'getFilename' => 'test.png',
            'getPathname' => '/tmp/test.png',
        ]);

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $imageUploadService->uploadUserImage($fileMock, 1);
    }

    public function testStreamResponseFromGetImage(): void
    {
        $userMock = $this->makeEmpty(UserInterface::class, [
            'getImage' => fopen('php://memory', 'r'),
        ]);

        $userRepositoryMock = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $userMock,
        ]);

        $securityServiceMock = $this->makeEmpty(SecurityServiceInterface::class);

        $assetResolver = $this->makeEmpty(AssetResolverInterface::class);

        $imageUploadService = new ImageService($userRepositoryMock, $securityServiceMock, $assetResolver);

        $response = $imageUploadService->getImageFromUserAsStreamedResponse(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }
}
