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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Contract;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authorization-server issuance contract.
 *
 * The embedded league/oauth2-server instance is one implementation; delegating
 * issuance to an external IdP (where this AS is simply absent) is another.
 * PSR-7 shaped to match league's framework-agnostic core.
 *
 * The two-phase authorize/consent flow (validate → render consent → complete)
 * is added later alongside the consent endpoints; this seam covers the
 * single-shot token and revocation endpoints.
 *
 * @internal
 */
interface TokenIssuerInterface
{
    /**
     * Handle a token request (RFC 6749 §3.2): auth-code exchange, refresh.
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface;

    /**
     * Handle a token revocation request (RFC 7009).
     */
    public function respondToRevocationRequest(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface;
}
