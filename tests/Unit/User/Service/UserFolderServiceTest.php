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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Doctrine\DBAL\Driver\PDO\Exception as PDODriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use PDOException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserFolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserFolderService;
use Pimcore\Model\User\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserFolderServiceTest extends Unit
{
    public function testDeleteUserFolderByIdAsNonAdminUser(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
        ]);
        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class);
        $userTreeNodeHydrator = $this->makeEmpty(UserTreeNodeHydratorInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);

        $this->expectExceptionMessage('Only admin users are allowed to delete user folders');
        $this->expectException(ForbiddenException::class);
        $userFolderService->deleteUserFolderById(1);
    }

    public function testDeleteUserFolderByIdWithDatabaseException(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class, [
            'getUserFolderById' => new Folder(),
            'deleteUserFolder' => function (Folder $folder) {
                throw new Exception('Database error');
            },
        ]);
        $userTreeNodeHydrator = $this->makeEmpty(UserTreeNodeHydratorInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to delete user folder with id 1: Database error');
        $userFolderService->deleteUserFolderById(1);
    }

    public function testDeleteUserFolderById(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
        ]);

        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class, [
            'getUserFolderById' => new Folder(),
            'deleteUserFolder' => Expected::once(),
        ]);
        $userTreeNodeHydrator = $this->makeEmpty(UserTreeNodeHydratorInterface::class);

        $userFolderService = new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);
        $userFolderService->deleteUserFolderById(1);
    }

    public function testCreateUserFolderThrowsConflictExceptionOnDuplicateName(): void
    {
        $userFolderService = $this->createServiceWithFailingCreate(
            new UniqueConstraintViolationException(
                PDODriverException::new(new PDOException('Duplicate entry')),
                null
            )
        );

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Folder with name "duplicate" already exists in this location');
        $userFolderService->createUserFolder(new CreateParameter(0, 'duplicate'));
    }

    public function testCreateUserFolderThrowsDatabaseExceptionOnGenericError(): void
    {
        $userFolderService = $this->createServiceWithFailingCreate(new Exception('something went wrong'));

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to create user folder: something went wrong');
        $userFolderService->createUserFolder(new CreateParameter(0, 'duplicate'));
    }

    private function createServiceWithFailingCreate(Exception $repositoryException): UserFolderService
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class);
        $userFolderRepository = $this->makeEmpty(UserFolderRepositoryInterface::class, [
            'createUserFolder' => static function () use ($repositoryException): void {
                throw $repositoryException;
            },
        ]);
        $userTreeNodeHydrator = $this->makeEmpty(UserTreeNodeHydratorInterface::class);

        return new UserFolderService($securityService, $userFolderRepository, $userTreeNodeHydrator);
    }
}
