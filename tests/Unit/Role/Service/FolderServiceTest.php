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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Role\Service;

use Codeception\Test\Unit;
use Doctrine\DBAL\Driver\PDO\Exception as PDODriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use PDOException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Role\Hydrator\RoleTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\FolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Service\FolderService;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class FolderServiceTest extends Unit
{
    public function testCreateFolderThrowsConflictExceptionOnDuplicateName(): void
    {
        $service = $this->createService(
            new UniqueConstraintViolationException(
                PDODriverException::new(new PDOException('Duplicate entry')),
                null
            )
        );

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Folder with name "duplicate" already exists in this location');

        $service->createFolder(new CreateParameter(0, 'duplicate'));
    }

    public function testCreateFolderThrowsDatabaseExceptionOnGenericError(): void
    {
        $service = $this->createService(new Exception('something went wrong'));

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to create folder with name duplicate: something went wrong');

        $service->createFolder(new CreateParameter(0, 'duplicate'));
    }

    private function createService(Exception $repositoryException): FolderService
    {
        $folderRepository = $this->makeEmpty(FolderRepositoryInterface::class, [
            'createFolder' => static function () use ($repositoryException): void {
                throw $repositoryException;
            },
        ]);

        return new FolderService(
            $folderRepository,
            $this->makeEmpty(RoleTreeNodeHydratorInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class)
        );
    }
}
