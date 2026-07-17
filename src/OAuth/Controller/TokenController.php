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
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function json_encode;

/**
 * OAuth token endpoint (POST /pimcore-oauth/token). Bridges the Symfony request
 * to PSR-7, delegates to the embedded league server, and bridges the response
 * back. Currently handles the client_credentials grant.
 *
 * @internal
 */
final class TokenController
{
    public function __construct(
        private readonly AuthorizationServerFactory $authorizationServerFactory,
        private readonly HttpMessageFactoryInterface $psrHttpFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $psrResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $psrResponse = $this->psrResponseFactory->createResponse();

        try {
            $psrResponse = $this->authorizationServerFactory->create()->respondToAccessTokenRequest(
                $this->psrHttpFactory->createRequest($request),
                $psrResponse,
            );
        } catch (OAuthServerException $exception) {
            $psrResponse = $exception->generateHttpResponse($psrResponse);
        } catch (Throwable) {
            $psrResponse = $this->psrResponseFactory->createResponse(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
            $psrResponse->getBody()->write((string) json_encode(['error' => 'server_error']));
        }

        return $this->httpFoundationFactory->createResponse($psrResponse);
    }
}
