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
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthClientRecord;
use Pimcore\Migrations\BundleAwareMigration;

/**
 * Bind a confidential OAuth client one to one to a service user, for the
 * client_credentials grant. Nullable: every existing client stays unbound.
 *
 * @internal
 */
final class Version20261105120000 extends BundleAwareMigration
{
    private const string COLUMN_NAME = 'service_user_id';

    private const string INDEX_NAME = 'uniq_oauth_client_service_user_id';

    public function getDescription(): string
    {
        return 'Add the service user binding to ' . OAuthClientRecord::TABLE_NAME;
    }

    public function isTransactional(): bool
    {
        return false;
    }

    protected function getBundleName(): string
    {
        return 'PimcoreStudioBackendBundle';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(OAuthClientRecord::TABLE_NAME)) {
            return;
        }

        // Guarded and built from the schema diff, like Version20260914120000, so the
        // migration is idempotent and portable across MySQL and MariaDB.
        $table = $schema->getTable(OAuthClientRecord::TABLE_NAME);

        if (!$table->hasColumn(self::COLUMN_NAME)) {
            $table->addColumn(self::COLUMN_NAME, 'integer', ['notnull' => false, 'unsigned' => true]);
        }

        // Unique: one client per service user. NULLs do not collide, so unbound clients
        // are unaffected.
        if (!$table->hasIndex(self::INDEX_NAME)) {
            $table->addUniqueIndex([self::COLUMN_NAME], self::INDEX_NAME);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(OAuthClientRecord::TABLE_NAME)) {
            return;
        }

        // Dropping the column drops every binding: the bound clients stay as unbound
        // confidential clients, which ClientRepository refuses client_credentials.
        $table = $schema->getTable(OAuthClientRecord::TABLE_NAME);

        if ($table->hasIndex(self::INDEX_NAME)) {
            $table->dropIndex(self::INDEX_NAME);
        }

        if ($table->hasColumn(self::COLUMN_NAME)) {
            $table->dropColumn(self::COLUMN_NAME);
        }
    }
}
