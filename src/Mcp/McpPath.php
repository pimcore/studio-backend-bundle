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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp;

/**
 * The URL space the MCP servers occupy.
 *
 * One declaration, because three used to exist: the firewall pattern, the prefix the
 * rate limiter matches on, and the base the OAuth audience is built from. Their
 * agreement was maintained by a comment asking the next reader to keep them in sync,
 * which is the kind of arrangement that holds until it does not.
 *
 * @internal
 */
final class McpPath
{
    /**
     * The MCP base, no trailing slash. Joined to the issuer it is the bundle's base OAuth
     * protected resource, which covers every `/pimcore-mcp/...` endpoint that registers
     * nothing more specific. A request is validated against the most specific registered
     * resource covering it (RequestResourceResolverInterface), so a managed MCP server
     * under {@see self::STUDIO} is held to its own audience, not to this one.
     */
    public const string BASE = '/pimcore-mcp';

    /**
     * The base as a path prefix, for matching requests that live under it.
     */
    public const string PREFIX = self::BASE . '/';

    /**
     * Anchored form of {@see self::PREFIX}, for the Symfony firewall map.
     */
    public const string FIREWALL_PATTERN = '^' . self::PREFIX;

    /**
     * Where the MCP servers managed through Studio are served, one per slug:
     * `/pimcore-mcp/studio/{slug}`. Each is its own protected resource.
     */
    public const string STUDIO = self::BASE . '/studio';

    /**
     * The slugs the `pimcore_studio_mcp_server` route accepts. Kept in step with the
     * `requirements` in config/pimcore/routing.yaml, which cannot reference a constant: a
     * server whose slug does not match is unreachable, so it is not advertised either.
     */
    public const string STUDIO_SLUG_PATTERN = '/^[a-z0-9-]+$/';
}
