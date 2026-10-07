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
     * The MCP base, no trailing slash. This is the OAuth protected-resource URI when
     * joined to the issuer: every `/pimcore-mcp/...` request is validated against this
     * one audience rather than against the sub-path that was called.
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
}
