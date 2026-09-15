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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Mcp\McpPath;
use Pimcore\Bundle\StudioBackendBundle\OAuth\OAuthPath;
use Symfony\Component\HttpFoundation\Request;
use function rawurldecode;
use function rtrim;
use function str_starts_with;

/**
 * @internal
 */
trait StudioBackendPathTrait
{
    /**
     * The MCP firewall serves a path space of its own, outside the Studio API url_prefix, so
     * anything that scopes itself to Studio traffic has to name it explicitly or MCP escapes.
     */
    private const string MCP_PATH_PREFIX = McpPath::PREFIX;

    private function isStudioBackendPath(string $path, string $urlPrefix): bool
    {
        return str_starts_with($path, $urlPrefix);
    }

    private function isMcpPath(string $path): bool
    {
        return str_starts_with($path, self::MCP_PATH_PREFIX);
    }

    /**
     * True for the authorization server's own root-level endpoints, and for the consent API
     * below the Studio prefix when one is passed.
     *
     * $apiPrefix is empty for callers that only mean the public OAuth endpoints. It must
     * stay empty rather than defaulting to something, because an unset Studio prefix would
     * otherwise turn into a bare "/oauth/" that could belong to the host application.
     */
    private function isOAuthPath(string $path, string $apiPrefix = ''): bool
    {
        foreach (OAuthPath::ROOT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        if ($apiPrefix === '') {
            return false;
        }

        return str_starts_with($path, rtrim($apiPrefix, '/') . OAuthPath::API_SUFFIX);
    }

    /**
     * The path the router will actually match on.
     *
     * Request::getPathInfo() is still percent-encoded, while the router matches on the
     * decoded path (CompiledUrlMatcherTrait::doMatch() calls rawurldecode() on it). Comparing
     * the raw path would let "/%70imcore-oauth/register" route to the registration controller
     * with the endpoint guard skipped and no rate limiter consumed, and the number of
     * encodings is unbounded. Everything in this trait matches on this value, for the same
     * reason and so that no two callers can disagree about which requests are theirs.
     *
     * Decoded exactly once, like the router: decoding repeatedly would claim paths the router
     * never routes here, so "%2570imcore-oauth" would be refused while the request it
     * describes 404s anyway.
     */
    private function routedPath(Request $request): string
    {
        return rawurldecode($request->getPathInfo());
    }
}
