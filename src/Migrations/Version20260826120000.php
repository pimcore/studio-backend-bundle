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

namespace Pimcore\Bundle\StudioBackendBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CacheKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Cache;

/**
 * Register the `mcp_servers` user permission for installations that were set up before it existed.
 * The permission is created on a fresh install by {@see \Pimcore\Bundle\StudioBackendBundle\Installer},
 * but that only runs once — existing instances need this migration to gate the MCP server management
 * endpoints. Without the row the permission voter cannot resolve the attribute and denies everyone,
 * including admins.
 *
 * @internal
 */
final class Version20260826120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the "' . UserPermissions::MCP_SERVERS->value . '" user permission definition';
    }

    public function up(Schema $schema): void
    {
        $table = UserPermissions::DEFINITIONS_TABLE->value;

        // Idempotent: a fresh install adds this via the installer, and forward-merges across release
        // lines can replay the change, so only insert when the row is absent.
        $exists = $this->connection->fetchOne(
            'SELECT `key` FROM `' . $table . '` WHERE `key` = ?',
            [UserPermissions::MCP_SERVERS->value]
        );

        if ($exists !== false) {
            return;
        }

        $this->addSql(
            'INSERT INTO `' . $table . '` (`key`, `category`) VALUES (?, ?)',
            [UserPermissions::MCP_SERVERS->value, UserPermissions::PERMISSIONS_CATEGORY->value]
        );
    }

    public function down(Schema $schema): void
    {
        $table = UserPermissions::DEFINITIONS_TABLE->value;

        $exists = $this->connection->fetchOne(
            'SELECT `key` FROM `' . $table . '` WHERE `key` = ?',
            [UserPermissions::MCP_SERVERS->value]
        );

        if ($exists === false) {
            return;
        }

        $this->addSql(
            'DELETE FROM `' . $table . '` WHERE `key` = ?',
            [UserPermissions::MCP_SERVERS->value]
        );
    }

    /**
     * The permission voter caches the permission-key list; drop it so the change takes effect
     * without a separate cache clear. Mirrors how the list is stored (see UserPermissionVoter).
     */
    public function postUp(Schema $schema): void
    {
        Cache::remove(CacheKeys::USER_PERMISSIONS->value);
    }

    public function postDown(Schema $schema): void
    {
        Cache::remove(CacheKeys::USER_PERMISSIONS->value);
    }
}
