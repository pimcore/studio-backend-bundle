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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Controller;

use League\OAuth2\Server\Exception\OAuthServerException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactory;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStore;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function bin2hex;
use function random_bytes;
use function str_contains;

/**
 * OAuth authorization endpoint (GET /pimcore-oauth/authorize). Validates the
 * request, stashes it under an opaque id, and redirects the browser to the
 * Studio UI consent screen. Approval happens through the consent API.
 *
 * @internal
 */
final class AuthorizeController
{
    public function __construct(
        private readonly AuthorizationServerFactory $authorizationServerFactory,
        private readonly PendingAuthorizationStore $pendingAuthorizationStore,
        private readonly HttpMessageFactoryInterface $psrHttpFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $psrResponseFactory,
        private readonly string $consentPath,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            // Validates client, redirect URI, scopes and PKCE before we prompt.
            $this->authorizationServerFactory->create()->validateAuthorizationRequest(
                $this->psrHttpFactory->createRequest($request)
            );
        } catch (OAuthServerException $exception) {
            return $this->httpFoundationFactory->createResponse(
                $exception->generateHttpResponse($this->psrResponseFactory->createResponse())
            );
        }

        $id = bin2hex(random_bytes(32));
        $this->pendingAuthorizationStore->store($id, $request->query->all());

        $separator = str_contains($this->consentPath, '?') ? '&' : '?';

        return new RedirectResponse($this->consentPath . $separator . 'authorization_id=' . $id);
    }
}
