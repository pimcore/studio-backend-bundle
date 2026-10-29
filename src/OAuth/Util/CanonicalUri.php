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

use function in_array;
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

    /**
     * A bare origin: scheme, host, optional port, and nothing else, already in canonical
     * form. This is the shape the issuer must have, because every URI the server derives
     * from it is the issuer with a root path appended, compared byte for byte.
     *
     * Canonicality is decided by canonicalize() rather than by a second set of rules, so
     * the shape accepted is exactly the shape compared. That is what rejects a trailing
     * slash, an uppercase host and a redundant default port; the explicit part checks
     * reject the components an origin may not carry at all.
     */
    public static function isCanonicalOrigin(string $uri): bool
    {
        if ($uri === '' || $uri !== trim($uri)) {
            return false;
        }

        $parts = parse_url($uri);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        foreach (['path', 'query', 'fragment', 'user', 'pass'] as $part) {
            if (($parts[$part] ?? '') !== '') {
                return false;
            }
        }

        return $uri === self::canonicalize($uri);
    }
}
