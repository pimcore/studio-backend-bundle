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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\FolderResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepository;
use Pimcore\Model\User\Folder;

/**
 * @internal
 */
final class UserFolderRepositoryTest extends Unit
{
    public function testDeleteUserFolder(): void
    {
        $folderResolverMock = $this->makeEmpty(FolderResolverInterface::class);
        $folderMock =  $this->makeEmpty(Folder::class, [
            'delete' => Expected::once(),
        ]);

        $folderRepository = new UserFolderRepository($folderResolverMock);

        $folderRepository->deleteUserFolder($folderMock);
    }

    public function testGetUserFolderByIdNoUserFound(): void
    {
        $folderId = 1;
        $folderResolverMock = $this->makeEmpty(FolderResolverInterface::class, [
            'getById' => null,
        ]);

        $folderRepository = new UserFolderRepository($folderResolverMock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User folder with ID: 1 not found');
        $folderRepository->getUserFolderById($folderId);
    }

    public function testGetUserFolderById(): void
    {
        $folderId = 1;
        $folder = new Folder();
        $folder->setId($folderId);

        $folderResolverMock = $this->makeEmpty(FolderResolverInterface::class, [
            'getById' => $folder,
        ]);
        $folderRepository = new UserFolderRepository($folderResolverMock);

        $this->assertSame($folder, $folderRepository->getUserFolderById($folderId));
    }
}
