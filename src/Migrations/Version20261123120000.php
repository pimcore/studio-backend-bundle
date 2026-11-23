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
 * The site update and delete endpoints require the "sites" permission. Databases created from a
 * dump without that core permission definition reject these requests for every user, admins
 * included, because no voter handles an undefined permission.
 *
 * @internal
 */
final class Version20261123120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the "' . UserPermissions::SITES->value . '" user permission definition if it is missing';
    }

    public function up(Schema $schema): void
    {
        $table = UserPermissions::DEFINITIONS_TABLE->value;

        $exists = $this->connection->fetchOne(
            'SELECT `key` FROM `' . $table . '` WHERE `key` = ?',
            [UserPermissions::SITES->value]
        );

        if ($exists !== false) {
            return;
        }

        $this->addSql(
            'INSERT INTO `' . $table . '` (`key`, `category`) VALUES (?, ?)',
            [UserPermissions::SITES->value, '']
        );
    }

    public function down(Schema $schema): void
    {
        // The definition belongs to Pimcore core and stays.
    }

    public function postUp(Schema $schema): void
    {
        // The voter caches the permission list.
        Cache::remove(CacheKeys::USER_PERMISSIONS->value);
    }
}
