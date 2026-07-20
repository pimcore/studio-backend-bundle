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
use Pimcore\Bundle\StudioBackendBundle\Entity\Notification\NotificationSubscription;

/**
 * Creates the per-user notification preferences table for existing installations. Fresh
 * installations get it from the Installer instead.
 *
 * @internal
 */
final class Version20260720120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the notification subscription table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(NotificationSubscription::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(NotificationSubscription::TABLE_NAME);

        $table->addColumn('user_id', 'integer', ['notnull' => true, 'unsigned' => true]);
        $table->addColumn('type_id', 'string', ['notnull' => true, 'length' => 190]);
        $table->addColumn('subscribed', 'boolean', ['notnull' => true, 'default' => true]);
        // Null means "never chosen" and falls back to the type defaults; an empty array is a
        // deliberate "no channels". Collapsing the two would resurrect defaults for anyone who
        // switched everything off.
        $table->addColumn('channels', 'json', ['notnull' => false]);

        $table->setPrimaryKey(['user_id', 'type_id'], 'pk_' . NotificationSubscription::TABLE_NAME);

        $table->addForeignKeyConstraint(
            'users',
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_' . NotificationSubscription::TABLE_NAME . '_users'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(NotificationSubscription::TABLE_NAME)) {
            return;
        }

        $schema->dropTable(NotificationSubscription::TABLE_NAME);
    }
}
