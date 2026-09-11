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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Util;

use function parse_url;
use function rtrim;
use function strtolower;
use function trim;

/**
 * Canonicalises a resource URI so the registry, the issued-token audience, and
 * the resource-server self-URI all derive the same value: lowercase scheme +
 * host, default ports dropped, no fragment, no trailing slash on the path.
 *
 * @internal
 */
final class CanonicalUri
{
    public static function canonicalize(string $uri): string
    {
        $uri = trim($uri);
        $parts = parse_url($uri);

        // Not a parseable absolute URI: fall back to a best-effort trim so a
        // mis-configured value still compares consistently with itself.
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return rtrim($uri, '/');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        $authority = $host . ($port !== null ? ':' . $port : '');
        $path = rtrim($parts['path'] ?? '', '/');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        // Fragment is intentionally dropped per the canonicalisation rules.
        return $scheme . '://' . $authority . $path . $query;
    }

    public static function equals(string $a, string $b): bool
    {
        return self::canonicalize($a) === self::canonicalize($b);
    }
}
