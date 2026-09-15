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
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthClientRecord;

/**
 * Record a digest of each dynamically registered client's metadata, so a repeat
 * registration of the same client is recognised and reused instead of creating a
 * new row. Without it, an open registration endpoint grows the table unbounded.
 *
 * @internal
 */
final class Version20260914120000 extends AbstractMigration
{
    private const string INDEX_NAME = 'idx_oauth_client_metadata_hash';

    private const string COLUMN_NAME = 'metadata_hash';

    public function getDescription(): string
    {
        return 'Add the metadata hash column to ' . OAuthClientRecord::TABLE_NAME;
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(OAuthClientRecord::TABLE_NAME)) {
            return;
        }

        // Guard on the column rather than emitting `ADD COLUMN IF NOT EXISTS`, which is a
        // MariaDB-only extension and a syntax error on MySQL. Letting Doctrine build the
        // ALTER from the schema diff keeps the DDL portable across both, and the guard
        // makes the migration idempotent for re-runs and cross-line forward-merges.
        $table = $schema->getTable(OAuthClientRecord::TABLE_NAME);

        if (!$table->hasColumn(self::COLUMN_NAME)) {
            // Nullable: existing rows keep a null hash and are therefore never matched,
            // so clients registered before this migration are left exactly as they are.
            $table->addColumn(self::COLUMN_NAME, 'string', ['length' => 64, 'notnull' => false]);
        }

        // Not unique: two confidential clients may carry identical metadata, and both
        // store a null hash rather than colliding. Lookups only ever ask for a non-null
        // digest, which the index answers without scanning the table.
        if (!$table->hasIndex(self::INDEX_NAME)) {
            $table->addIndex([self::COLUMN_NAME], self::INDEX_NAME);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(OAuthClientRecord::TABLE_NAME)) {
            return;
        }

        $table = $schema->getTable(OAuthClientRecord::TABLE_NAME);

        if ($table->hasIndex(self::INDEX_NAME)) {
            $table->dropIndex(self::INDEX_NAME);
        }

        if ($table->hasColumn(self::COLUMN_NAME)) {
            $table->dropColumn(self::COLUMN_NAME);
        }
    }
}
