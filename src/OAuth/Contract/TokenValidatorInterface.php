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

use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ResolvedAccess;

/**
 * Resource-server contract: validate a raw bearer token presented at a
 * protected resource and resolve it to effective access.
 *
 * The MCP firewall authenticator depends only on this contract, so the token
 * source (embedded AS today, external IdP later) is swappable without changing
 * the endpoints.
 *
 * Public API. Bundles that expose their own OAuth-protected endpoints implement
 * an authenticator against this contract rather than duplicating token parsing,
 * signature and revocation checks.
 */
interface TokenValidatorInterface
{
    /**
     * Audience binding (RFC 8707) is part of this contract, not left to the caller: a token
     * that is not bound to $resourceUri, including one that names no audience at all, must
     * yield null. Callers rely on that to keep a token minted for one resource from opening
     * another, and do not repeat the comparison.
     *
     * @return ResolvedAccess|null resolved access, or null if the token is not
     *                             ours / invalid / expired / revoked / not bound to $resourceUri
     */
    public function validate(string $rawToken, string $resourceUri): ?ResolvedAccess;
}
