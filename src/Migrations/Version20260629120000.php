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

/**
 * Adds the `theme` column to the `users` table for installations that predate
 * it. The column backs the per-user theme selection exposed through the user
 * profile / user management APIs (UpdateUserProfile, UserInformation): without
 * it the Pimcore User DAO silently drops the value on save and always reads
 * back the model default. Matches the definition shipped in the Pimcore
 * install schema (varchar(255) NOT NULL DEFAULT 'default').
 *
 * @internal
 */
final class Version20260629120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the theme column to the users table (per-user theme selection)';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('users');
        if (!$table->hasColumn('theme')) {
            $table->addColumn('theme', 'string', [
                'length' => 255,
                'notnull' => true,
                'default' => 'default',
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');
        if ($table->hasColumn('theme')) {
            $table->dropColumn('theme');
        }
    }
}
