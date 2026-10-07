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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Hydrator;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsentClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsentUser;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\RedirectHost;
use Pimcore\Model\UserInterface;
use function array_map;
use function array_values;
use function is_string;

/**
 * Turns a validated league authorization request into the payload the consent screen reads.
 *
 * @internal
 */
final readonly class AuthorizationConsentHydrator implements AuthorizationConsentHydratorInterface
{
    public function hydrate(
        string $authorizationId,
        AuthorizationRequestInterface $authorizationRequest,
        ?UserInterface $user,
    ): AuthorizationConsent {
        $client = $authorizationRequest->getClient();

        return new AuthorizationConsent(
            $authorizationId,
            new AuthorizationConsentClient(
                $client->getIdentifier(),
                $client->getName(),
                RedirectHost::fromUri($authorizationRequest->getRedirectUri() ?? $this->firstRedirectUri($client)),
                // Only a client an administrator declared in configuration is treated as
                // verified; a self-registered or URL-identified one is shown as unverified.
                $client instanceof ClientEntity && $client->isPreRegistered(),
            ),
            // The scopes carried on the request, which league has already narrowed to what
            // the named resource supports. Showing anything else would make the screen
            // promise something the token will not carry.
            array_values(array_map(
                static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
                $authorizationRequest->getScopes(),
            )),
            // Both accessors are nullable on Pimcore's user model, and the consent payload
            // needs a real id to name who would be acting. A user we cannot identify is
            // reported as none rather than as a user with invented values.
            $user !== null && $user->getId() !== null
                ? new AuthorizationConsentUser($user->getId(), (string) $user->getName())
                : null,
        );
    }

    /**
     * league models a single redirect URI as a string and several as a list, so both shapes
     * reach here. Used only when the request names none of its own.
     */
    private function firstRedirectUri(ClientEntityInterface $client): ?string
    {
        $redirectUri = $client->getRedirectUri();
        if (is_string($redirectUri)) {
            return $redirectUri !== '' ? $redirectUri : null;
        }

        return $redirectUri[0] ?? null;
    }
}
