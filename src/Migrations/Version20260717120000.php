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
 * Create the OAuth token record table used for access-token revocation and,
 * later, refresh-token reuse detection.
 *
 * @internal
 */
final class Version20260717120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the OAuth token record table (' . OAuthTokenRecord::TABLE_NAME . ')';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // No FK to users: revocation of a deleted user's tokens is handled at the
        // application layer, and an INT FK across differing table collations trips
        // MariaDB. The user_id column is indexed for lookups/cleanup instead.
        $this->addSql('
            CREATE TABLE IF NOT EXISTS ' . OAuthTokenRecord::TABLE_NAME . ' (
                identifier VARCHAR(128) NOT NULL,
                type VARCHAR(16) NOT NULL,
                expires_at BIGINT UNSIGNED NOT NULL,
                revoked TINYINT(1) NOT NULL DEFAULT 0,
                user_id INT UNSIGNED NULL,
                client_id VARCHAR(255) NULL,
                created_at BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (identifier),
                INDEX idx_oauth_token_user (user_id),
                INDEX idx_oauth_token_expires (expires_at)
            ) DEFAULT CHARSET=utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ' . OAuthTokenRecord::TABLE_NAME);
    }
}
