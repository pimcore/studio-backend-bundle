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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Repository;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StaticResolverBundle\Models\User\FolderResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepository;
use Pimcore\Model\User\Folder;

/**
 * @internal
 */
final class UserFolderRepositoryTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepository::deleteUserFolder
     */
    public function testDeleteUserFolder(): void
    {
        $folderResolverMock = $this->createMock(FolderResolverInterface::class);
        $folderMock = $this->createMock(Folder::class);
        $folderMock->expects($this->once())->method('delete');

        $folderRepository = new UserFolderRepository($folderResolverMock);

        $folderRepository->deleteUserFolder($folderMock);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepository::getUserFolderById
     */
    public function testGetUserFolderByIdNoUserFound(): void
    {
        $folderId = 1;
        $folderResolverMock = $this->createMock(FolderResolverInterface::class);
        $folderResolverMock->method('getById')->willReturn(null);

        $folderRepository = new UserFolderRepository($folderResolverMock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User folder with ID: 1 not found');
        $folderRepository->getUserFolderById($folderId);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepository::getUserFolderById
     */
    public function testGetUserFolderById(): void
    {
        $folderId = 1;
        $folder = new Folder();
        $folder->setId($folderId);

        $folderResolverMock = $this->createMock(FolderResolverInterface::class);
        $folderResolverMock->method('getById')->willReturn($folder);
        
        $folderRepository = new UserFolderRepository($folderResolverMock);

        $this->assertSame($folder, $folderRepository->getUserFolderById($folderId));
    }
}
