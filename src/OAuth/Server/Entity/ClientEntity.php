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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use function in_array;

/**
 * An OAuth client (pre-registered, CIMD or dynamically registered). Public clients
 * authenticate via PKCE; confidential dynamic clients carry a secret validated at the
 * token endpoint.
 *
 * @internal
 */
final class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    /**
     * @param string|string[]   $redirectUri
     * @param list<string>|null $grantTypes the grants this client registered for, or null
     *                                      when it never declared a restriction
     * @param list<string>|null $scopes     the scopes this client registered for, or null
     *                                      when it never declared any
     */
    public function __construct(
        string $identifier,
        string $name,
        string|array $redirectUri,
        bool $isConfidential = false,
        private readonly bool $preRegistered = false,
        private readonly ?array $grantTypes = null,
        private readonly ?array $scopes = null,
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->isConfidential = $isConfidential;
    }

    /**
     * True only for clients an administrator declared in configuration, as
     * opposed to self-registered (DCR) or URL-identified (CIMD) clients. The
     * consent screen uses this to mark self-registered clients as unverified.
     */
    public function isPreRegistered(): bool
    {
        return $this->preRegistered;
    }

    /**
     * Overrides league's ClientTrait, which answers true for everything.
     *
     * league consults this in two places: AbstractGrant::getClientEntityOrFail() refuses
     * the whole request with `unauthorized_client` when a client uses a grant it did not
     * register, and AbstractGrant::issueRefreshToken() quietly omits the refresh token
     * when the client did not register `refresh_token`. So a client registered for
     * `authorization_code` alone still completes that flow and receives an access token;
     * it simply gets no refresh token, and is refused if it tries the refresh grant.
     *
     * Null means the client never declared a restriction, which is every client an
     * operator declared in `oauth.clients` and every CIMD client: neither form carries
     * `grant_types`, and inferring a restriction from its absence would silently narrow
     * what those clients can do. Dynamically registered clients always carry at least
     * `authorization_code` (see ClientRegistrar::parseGrantTypes()), so the null case
     * never means "a DCR client that registered nothing".
     */
    public function supportsGrantType(string $grantType): bool
    {
        return $this->grantTypes === null || in_array($grantType, $this->grantTypes, true);
    }

    /**
     * The scopes this client registered for, or null when it declared none.
     *
     * Carried here so the scope work can intersect against it, and deliberately consulted
     * by nothing yet: see ScopeRepository::finalizeScopes().
     *
     * @return list<string>|null
     */
    public function getRegisteredScopes(): ?array
    {
        return $this->scopes;
    }
}
