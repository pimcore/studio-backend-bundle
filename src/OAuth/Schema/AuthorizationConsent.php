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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

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
final class AuthorizationConsent implements AdditionalAttributesInterface
{
    // Additional attributes are how this bundle's pre-response events let an integration
    // add to a payload, and the only thing AuthorizationConsentEvent may do to this one.
    // Everything the user is shown stays readonly below: the scopes in particular are what
    // the authorization request actually carries, and a screen that disagreed with the
    // token that follows would be worse than no screen.
    use AdditionalAttributesTrait;

    /**
     * @param string[] $scopes
     */
    public function __construct(
        #[Property(description: 'Opaque id of the pending authorization', type: 'string', example: 'a1b2c3')]
        private readonly string $authorizationId,
        #[Property(ref: AuthorizationConsentClient::class)]
        private readonly AuthorizationConsentClient $client,
        #[Property(
            description: 'Requested scopes',
            type: 'array',
            items: new Items(type: 'string', example: 'mcp:read'),
        )]
        private readonly array $scopes,
        #[Property(ref: AuthorizationConsentUser::class, nullable: true)]
        private readonly ?AuthorizationConsentUser $user,
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
