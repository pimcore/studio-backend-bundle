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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * Details of a pending authorization shown on the Studio UI consent screen.
 *
 * @internal
 */
#[Schema(
    schema: 'AuthorizationConsent',
    title: 'OAuth Authorization Consent',
    required: ['authorizationId', 'client', 'scopes'],
    type: 'object',
)]
final readonly class AuthorizationConsent
{
    /**
     * @param string[] $scopes
     */
    public function __construct(
        #[Property(description: 'Opaque id of the pending authorization', type: 'string', example: 'a1b2c3')]
        private string $authorizationId,
        #[Property(ref: AuthorizationConsentClient::class)]
        private AuthorizationConsentClient $client,
        #[Property(
            description: 'Requested scopes',
            type: 'array',
            items: new Items(type: 'string', example: 'mcp:read'),
        )]
        private array $scopes,
        #[Property(ref: AuthorizationConsentUser::class, nullable: true)]
        private ?AuthorizationConsentUser $user,
    ) {
    }

    public function getAuthorizationId(): string
    {
        return $this->authorizationId;
    }

    public function getClient(): AuthorizationConsentClient
    {
        return $this->client;
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getUser(): ?AuthorizationConsentUser
    {
        return $this->user;
    }
}
