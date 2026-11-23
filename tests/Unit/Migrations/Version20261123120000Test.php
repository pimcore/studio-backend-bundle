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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Migrations;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\StudioBackendBundle\Migrations\Version20261123120000;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class Version20261123120000Test extends Unit
{
    public function testUpAddsTheSitesDefinitionWhenItIsMissing(): void
    {
        $migration = $this->createMigration(false);

        $migration->up(new Schema());

        $queries = $migration->getSql();
        $this->assertCount(1, $queries);
        $this->assertSame(
            'INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES (?, ?)',
            $queries[0]->getStatement()
        );
        $this->assertSame(['sites', ''], $queries[0]->getParameters());
    }

    public function testUpDoesNothingWhenTheSitesDefinitionExists(): void
    {
        $migration = $this->createMigration('sites');

        $migration->up(new Schema());

        $this->assertSame([], $migration->getSql());
    }

    public function testDownKeepsTheSitesDefinition(): void
    {
        $migration = $this->createMigration('sites');

        $migration->down(new Schema());

        $this->assertSame([], $migration->getSql());
    }

    private function createMigration(string|false $existingKey): Version20261123120000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn($existingKey);

        return new Version20261123120000($connection, new NullLogger());
    }
}
