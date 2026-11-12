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
     *                                      when this client source carries no grant metadata
     * @param list<string>|null $scopes     the scopes this client registered for, or null
     *                                      when this client source carries no scope metadata
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
     * Null means this client source carries no grant metadata at all, not that a client
     * declared an empty list. It is every client an operator wrote into `oauth.clients`,
     * where the configuration has no such key, and every CIMD client, whose document may
     * declare `grant_types` but whose metadata this bundle does not read. Inferring a
     * restriction from that silence would silently narrow what those clients can do.
     *
     * A dynamically registered client never produces null, and never produces an empty
     * list either: ClientRegistrar::parseGrantTypes() always returns at least
     * `authorization_code`.
     */
    public function supportsGrantType(string $grantType): bool
    {
        return $this->grantTypes === null || in_array($grantType, $this->grantTypes, true);
    }

    /**
     * The scopes this client registered for, or null when this client source carries no
     * scope metadata.
     *
     * The distinction matters for the deferred intersection, and the two are not the same
     * thing. Null is a config-declared or CIMD client, for which no scope metadata exists
     * to intersect against. An empty array is a dynamically registered client that
     * registered without naming any scope, which ClientRegistrar::parseScopes() normalises
     * to `[]` and which is the common DCR case: whether that means "no restriction
     * expressed" or "nothing permitted" is precisely the policy question left open.
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
