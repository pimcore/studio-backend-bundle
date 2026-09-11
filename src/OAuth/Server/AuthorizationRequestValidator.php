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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Re-validates stored authorization-request parameters into a league
 * AuthorizationRequest, so the consent details and approval steps operate on a
 * freshly validated request rather than a serialized one.
 *
 * @internal
 */
final readonly class AuthorizationRequestValidator
{
    public function __construct(
        private AuthorizationServerFactory $authorizationServerFactory,
        private ServerRequestFactoryInterface $serverRequestFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public function validate(array $queryParams): ?AuthorizationRequestInterface
    {
        $request = $this->serverRequestFactory
            ->createServerRequest('GET', '/pimcore-oauth/authorize')
            ->withQueryParams($queryParams);

        try {
            return $this->authorizationServerFactory->create()->validateAuthorizationRequest($request);
        } catch (OAuthServerException) {
            return null;
        }
    }
}
