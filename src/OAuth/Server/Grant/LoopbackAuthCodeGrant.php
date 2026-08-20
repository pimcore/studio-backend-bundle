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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant;

use DateInterval;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\RedirectUri\LoopbackRedirectUriValidator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authorization-code grant that validates redirect URIs with the RFC 8252
 * loopback exception, optionally extended to `localhost` (see
 * {@see LoopbackRedirectUriValidator}), and requires the S256 PKCE
 * transformation. The redirect-URI and PKCE-method validation are overridden;
 * all other behaviour is the league grant's.
 *
 * @internal
 */
final class LoopbackAuthCodeGrant extends AuthCodeGrant
{
    private const string REQUIRED_CODE_CHALLENGE_METHOD = 'S256';

    /**
     * OAuth 2.1 and our advertised metadata support only the S256 PKCE
     * transformation, but the league grant still accepts `plain` (and defaults
     * to it when the method is omitted). Reject anything but S256 before
     * delegating, so the server never issues a code protected by a plain
     * challenge.
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface
    {
        if ($this->getQueryStringParameter('code_challenge', $request) !== null) {
            $method = $this->getQueryStringParameter('code_challenge_method', $request, 'plain');

            if ($method !== self::REQUIRED_CODE_CHALLENGE_METHOD) {
                throw OAuthServerException::invalidRequest(
                    'code_challenge_method',
                    'Only the S256 code challenge method is supported.'
                );
            }
        }

        return parent::validateAuthorizationRequest($request);
    }

    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        DateInterval $authCodeTTL,
        private readonly bool $allowLocalhostLoopback,
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);
    }

    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request,
    ): void {
        $validator = new LoopbackRedirectUriValidator($client->getRedirectUri(), $this->allowLocalhostLoopback);

        if (!$validator->validateRedirectUri($redirectUri)) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }
    }
}
