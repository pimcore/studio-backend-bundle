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
 * A client created at runtime through RFC 7591 Dynamic Client Registration,
 * keyed by its generated identifier. Config-defined first-party clients are not
 * stored here; the client repository consults both sources.
 *
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: OAuthClientRecord::TABLE_NAME)]
class OAuthClientRecord
{
    public const string TABLE_NAME = 'bundle_studio_oauth_client';

    #[ORM\Id]
    #[ORM\Column(name: 'client_id', type: 'string', length: 128)]
    private string $clientId;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'redirect_uris', type: 'json')]
    private array $redirectUris;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'grant_types', type: 'json')]
    private array $grantTypes;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'scopes', type: 'json')]
    private array $scopes;

    #[ORM\Column(name: 'confidential', type: 'boolean', options: ['default' => false])]
    private bool $confidential;

    #[ORM\Column(name: 'secret_hash', type: 'string', length: 255, nullable: true)]
    private ?string $secretHash;

    #[ORM\Column(name: 'token_endpoint_auth_method', type: 'string', length: 40)]
    private string $tokenEndpointAuthMethod;

    #[ORM\Column(name: 'created_at', type: 'bigint', options: ['unsigned' => true])]
    private string $createdAt;

    /**
     * @param list<string> $redirectUris
     * @param list<string> $grantTypes
     * @param list<string> $scopes
     */
    public function __construct(
        string $clientId,
        string $name,
        array $redirectUris,
        array $grantTypes,
        array $scopes,
        bool $confidential,
        ?string $secretHash,
        string $tokenEndpointAuthMethod,
        int $createdAt,
    ) {
        $this->clientId = $clientId;
        $this->name = $name;
        $this->redirectUris = $redirectUris;
        $this->grantTypes = $grantTypes;
        $this->scopes = $scopes;
        $this->confidential = $confidential;
        $this->secretHash = $secretHash;
        $this->tokenEndpointAuthMethod = $tokenEndpointAuthMethod;
        $this->createdAt = (string) $createdAt;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * @return list<string>
     */
    public function getGrantTypes(): array
    {
        return $this->grantTypes;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function getSecretHash(): ?string
    {
        return $this->secretHash;
    }

    public function getTokenEndpointAuthMethod(): string
    {
        return $this->tokenEndpointAuthMethod;
    }

    public function getCreatedAt(): int
    {
        return (int) $this->createdAt;
    }
}
