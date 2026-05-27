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
use Pimcore\Bundle\StudioBackendBundle\Entity\Mcp\McpAccessToken;

/**
 * @internal
 */
final class Version20260519120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the MCP access token table';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS ' . McpAccessToken::TABLE_NAME . ' (
                token_hash VARCHAR(64) NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                reference VARCHAR(255) NOT NULL,
                expires_at BIGINT UNSIGNED NOT NULL,
                created_at BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (token_hash),
                INDEX idx_mcp_token_reference (reference),
                INDEX idx_mcp_token_user (user_id),
                INDEX idx_mcp_token_expires (expires_at),
                CONSTRAINT fk_' . McpAccessToken::TABLE_NAME . '_users
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ' . McpAccessToken::TABLE_NAME);
    }
}
