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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\StudioBackendBundle\Installer;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use function in_array;

/**
 * The permission key is the primary key of users_permission_definitions. The maintenance_mode
 * row is kept on uninstall (the classic admin checks the same key) and may be owned by another
 * bundle, so the installer must skip a row that already exists instead of failing the install.
 *
 * @internal
 */
final class InstallerUserPermissionTest extends Unit
{
    /**
     * @var string[]
     */
    private array $inserted = [];

    protected function _before(): void
    {
        $this->inserted = [];
    }

    public function testAnExistingPermissionRowIsNotInsertedAgain(): void
    {
        $installer = $this->createInstaller([UserPermissions::MAINTENANCE_MODE->value]);

        $installer->addUserPermission($this->createSchemaWithDefinitionsTable());

        $this->assertNotContains(UserPermissions::MAINTENANCE_MODE->value, $this->inserted);
        $this->assertContains(UserPermissions::MCP_SERVERS->value, $this->inserted);
    }

    public function testAMissingPermissionRowIsInserted(): void
    {
        $installer = $this->createInstaller([]);

        $installer->addUserPermission($this->createSchemaWithDefinitionsTable());

        $this->assertContains(UserPermissions::MAINTENANCE_MODE->value, $this->inserted);
        $this->assertContains(UserPermissions::MCP_SERVERS->value, $this->inserted);
    }

    public function testNothingIsInsertedWithoutTheDefinitionsTable(): void
    {
        $installer = $this->createInstaller([]);

        $installer->addUserPermission(new Schema());

        $this->assertSame([], $this->inserted);
    }

    /**
     * @param string[] $existingKeys
     */
    private function createInstaller(array $existingKeys): Installer
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn (string $identifier): string => '`' . $identifier . '`');
        $connection
            ->method('createQueryBuilder')
            ->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($connection));
        $connection
            ->method('fetchOne')
            ->willReturnCallback(
                static fn (string $sql, array $params): mixed => in_array($params['key'], $existingKeys, true)
                    ? $params['key']
                    : false
            );
        $connection
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params): int {
                $this->inserted[] = (string) $params['key'];

                return 1;
            });

        return new Installer($connection, $this->createMock(BundleInterface::class));
    }

    private function createSchemaWithDefinitionsTable(): Schema
    {
        $schema = new Schema();
        $schema->createTable(UserPermissions::DEFINITIONS_TABLE->value);

        return $schema;
    }
}
