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
        $this->addSql(
            'ALTER TABLE ' . OAuthTokenRecord::TABLE_NAME . ' ADD COLUMN IF NOT EXISTS resource VARCHAR(512) NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . OAuthTokenRecord::TABLE_NAME . ' DROP COLUMN IF EXISTS resource');
    }
}
