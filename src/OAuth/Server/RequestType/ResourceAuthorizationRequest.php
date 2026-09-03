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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RequestType;

use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;

/**
 * An authorization request that remembers the RFC 8707 `resource` it named.
 *
 * league has no concept of resource indicators, so the value has to travel on the
 * request object from validation through to completion, where it is written into the
 * authorization code and ends up as the access token's `aud`.
 *
 * @internal
 */
final class ResourceAuthorizationRequest extends AuthorizationRequest
{
    private ?string $resource = null;

    /**
     * Copies an already-validated league request, so validation stays entirely
     * league's and this class only carries the extra field.
     */
    public static function from(AuthorizationRequestInterface $request, ?string $resource): self
    {
        $copy = new self();
        $copy->setGrantTypeId($request->getGrantTypeId());
        $copy->setClient($request->getClient());
        $copy->setRedirectUri($request->getRedirectUri());
        $copy->setScopes($request->getScopes());
        $copy->setAuthorizationApproved($request->isAuthorizationApproved());

        // Every one of these is optional on the wire, and league's getters return null
        // for an absent value while its setters take a non-nullable string. Copying one
        // through unguarded turns a spec-legal request into a TypeError, which no
        // OAuthServerException handler catches, so the whole flow answers 500.
        $state = $request->getState();
        if ($state !== null) {
            $copy->setState($state);
        }

        $codeChallenge = $request->getCodeChallenge();
        if ($codeChallenge !== null) {
            $copy->setCodeChallenge($codeChallenge);
        }

        $codeChallengeMethod = $request->getCodeChallengeMethod();
        if ($codeChallengeMethod !== null) {
            $copy->setCodeChallengeMethod($codeChallengeMethod);
        }

        $user = $request->getUser();
        if ($user !== null) {
            $copy->setUser($user);
        }

        $copy->resource = $resource;

        return $copy;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }
}
