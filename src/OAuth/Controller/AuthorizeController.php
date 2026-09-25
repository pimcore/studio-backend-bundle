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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\MissingKeyMaterialException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\AuthorizationConsentServiceInterface;
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
        private readonly AuthorizationServerFactoryInterface $authorizationServerFactory,
        private readonly PendingAuthorizationStoreInterface $pendingAuthorizationStore,
        private readonly AuthorizationConsentServiceInterface $authorizationConsentService,
        private readonly HttpMessageFactoryInterface $psrHttpFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $psrResponseFactory,
        private readonly string $consentPath,
    ) {
    }

    /**
     * @throws MissingKeyMaterialException
     */
    public function __invoke(Request $request): Response
    {
        try {
            // Validates client, redirect URI, scopes and PKCE before we prompt.
            $authorizationRequest = $this->authorizationServerFactory->create()->validateAuthorizationRequest(
                $this->psrHttpFactory->createRequest($request)
            );
        } catch (OAuthServerException $exception) {
            return $this->httpFoundationFactory->createResponse(
                $exception->generateHttpResponse($this->psrResponseFactory->createResponse())
            );
        }

        $id = bin2hex(random_bytes(32));
        $this->pendingAuthorizationStore->store(
            $id,
            $this->authorizationConsentService->pinConsentParameters(
                $request->query->all(),
                $authorizationRequest,
            ),
        );

        $separator = str_contains($this->consentPath, '?') ? '&' : '?';

        return new RedirectResponse($this->consentPath . $separator . 'authorization_id=' . $id);
    }
}
