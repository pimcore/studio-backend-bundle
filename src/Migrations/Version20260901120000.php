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
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;

/**
 * Record the protected resource a token is bound to (RFC 8707).
 *
 * Storing it here is what lets the authorization-code and refresh exchanges carry the
 * binding without duplicating league's payload construction.
 *
 * @internal
 */
final class Version20260901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the resource column to ' . OAuthTokenRecord::TABLE_NAME;
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(OAuthTokenRecord::TABLE_NAME)) {
            return;
        }

        // Guard on the column rather than emitting `ADD COLUMN IF NOT EXISTS`, which is a
        // MariaDB-only extension and a syntax error on MySQL. Letting Doctrine build the
        // ALTER from the schema diff keeps the DDL portable across both, and the guard
        // makes the migration idempotent for re-runs and cross-line forward-merges.
        $table = $schema->getTable(OAuthTokenRecord::TABLE_NAME);
        if (!$table->hasColumn('resource')) {
            $table->addColumn('resource', 'string', ['length' => 512, 'notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(OAuthTokenRecord::TABLE_NAME)) {
            return;
        }

        $table = $schema->getTable(OAuthTokenRecord::TABLE_NAME);
        if ($table->hasColumn('resource')) {
            $table->dropColumn('resource');
        }
    }
}
