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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Mcp;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: McpAccessToken::TABLE_NAME)]
#[ORM\Index(columns: ['reference'], name: 'idx_mcp_token_reference')]
#[ORM\Index(columns: ['user_id'], name: 'idx_mcp_token_user')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_mcp_token_expires')]
class McpAccessToken
{
    public const string TABLE_NAME = 'bundle_studio_mcp_access_token';

    #[ORM\Id]
    #[ORM\Column(name: 'token_hash', type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'user_id', type: 'integer', options: ['unsigned' => true])]
    private int $userId;

    #[ORM\Column(name: 'reference', type: 'string', length: 255)]
    private string $reference;

    #[ORM\Column(name: 'expires_at', type: 'bigint', options: ['unsigned' => true])]
    private string $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'bigint', options: ['unsigned' => true])]
    private string $createdAt;

    public function __construct(
        string $tokenHash,
        int $userId,
        string $reference,
        int $expiresAt,
        int $createdAt,
    ) {
        $this->tokenHash = $tokenHash;
        $this->userId = $userId;
        $this->reference = $reference;
        $this->expiresAt = (string) $expiresAt;
        $this->createdAt = (string) $createdAt;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getExpiresAt(): int
    {
        return (int) $this->expiresAt;
    }

    public function setExpiresAt(int $expiresAt): void
    {
        $this->expiresAt = (string) $expiresAt;
    }

    public function getCreatedAt(): int
    {
        return (int) $this->createdAt;
    }
}
