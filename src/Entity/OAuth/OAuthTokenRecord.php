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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tracking record for an issued OAuth artifact (access token, refresh token or
 * auth code), keyed by its identifier. Enables revocation and reuse detection
 * for the otherwise self-contained JWTs, and grant/token listing.
 *
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: OAuthTokenRecord::TABLE_NAME)]
#[ORM\Index(columns: ['user_id'], name: 'idx_oauth_token_user')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_oauth_token_expires')]
class OAuthTokenRecord
{
    public const string TABLE_NAME = 'bundle_studio_oauth_token';

    public const string TYPE_ACCESS = 'access';

    public const string TYPE_REFRESH = 'refresh';

    public const string TYPE_AUTH_CODE = 'auth_code';

    #[ORM\Id]
    #[ORM\Column(name: 'identifier', type: 'string', length: 128)]
    private string $identifier;

    #[ORM\Column(name: 'type', type: 'string', length: 16)]
    private string $type;

    #[ORM\Column(name: 'expires_at', type: 'bigint', options: ['unsigned' => true])]
    private string $expiresAt;

    #[ORM\Column(name: 'revoked', type: 'boolean', options: ['default' => false])]
    private bool $revoked = false;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $userId;

    #[ORM\Column(name: 'client_id', type: 'string', length: 255, nullable: true)]
    private ?string $clientId;

    #[ORM\Column(name: 'created_at', type: 'bigint', options: ['unsigned' => true])]
    private string $createdAt;

    public function __construct(
        string $identifier,
        string $type,
        int $expiresAt,
        ?int $userId,
        ?string $clientId,
        int $createdAt,
    ) {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->expiresAt = (string) $expiresAt;
        $this->userId = $userId;
        $this->clientId = $clientId;
        $this->createdAt = (string) $createdAt;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExpiresAt(): int
    {
        return (int) $this->expiresAt;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getCreatedAt(): int
    {
        return (int) $this->createdAt;
    }
}
