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
 * Create the table backing RFC 7591 Dynamic Client Registration.
 *
 * @internal
 */
final class Version20260724120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the OAuth dynamic client table (' . OAuthClientRecord::TABLE_NAME . ')';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS ' . OAuthClientRecord::TABLE_NAME . ' (
                client_id VARCHAR(128) NOT NULL,
                name VARCHAR(255) NOT NULL,
                redirect_uris JSON NOT NULL,
                grant_types JSON NOT NULL,
                scopes JSON NOT NULL,
                confidential TINYINT(1) NOT NULL DEFAULT 0,
                secret_hash VARCHAR(255) NULL,
                token_endpoint_auth_method VARCHAR(40) NOT NULL,
                created_at BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (client_id)
            ) DEFAULT CHARSET=utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ' . OAuthClientRecord::TABLE_NAME);
    }
}
