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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Resolver;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\HttpFoundation\Request;
use function is_array;
use function parse_url;
use function rtrim;
use function str_starts_with;
use function strlen;

/**
 * @internal
 */
final readonly class RequestResourceResolver implements RequestResourceResolverInterface
{
    use StudioBackendPathTrait;

    public function __construct(
        private ResourceRegistryInterface $resourceRegistry,
        private ?string $issuer = null,
    ) {
    }

    public function resolve(Request $request): ?ProtectedResource
    {
        // Resources are registered under the issuer, so the request has to be expressed
        // the same way; behind a proxy the request host is not the issuer. There is
        // deliberately no fallback to the request host when no issuer is configured:
        // TokenValidatorInterface compares a token's `aud` against the URI it is handed,
        // so an audience rebuilt from the caller-supplied `Host` would be checked against
        // itself and pass (#2039).
        if ($this->issuer === null) {
            return null;
        }

        // The routed path, decoded once like the router: the raw path is still
        // percent-encoded, so `/pimcore-mcp/studio/%70roduct-read` would reach the
        // product-read server while resolving only to the broader /pimcore-mcp base,
        // and a token bound to the base would be accepted there.
        $target = CanonicalUri::canonicalize(rtrim($this->issuer, '/') . $this->routedPath($request));

        $match = null;
        $matchLength = -1;
        foreach ($this->resourceRegistry->all() as $resource) {
            $length = self::coveredPathLength($resource->canonicalUri, $target);

            // Longest matching path wins, so a resource registered for one server takes
            // precedence over a broader one registered for its whole prefix. Ranking on
            // the path rather than the whole URI keeps the choice a property of the
            // request, not of how long the rest of the identifier happens to be.
            if ($length > $matchLength) {
                $match = $resource;
                $matchLength = $length;
            }
        }

        return $match;
    }

    /**
     * How much of the request path a resource covers, or -1 when it does not cover it
     * at all. The rule a standards-based client applies when deciding whether a resource
     * covers an endpoint: same origin, and a path prefix that only matches on a segment
     * boundary, so `/pimcore-mcp/agent` never covers `/pimcore-mcp/agentx`.
     */
    private static function coveredPathLength(string $resourceUri, string $target): int
    {
        $resource = parse_url($resourceUri);
        $endpoint = parse_url($target);

        if (!is_array($resource) || !is_array($endpoint)) {
            return -1;
        }

        // RFC 8707 resource identifiers carry no query or fragment. One that does can
        // never be what this endpoint is, and matching it on path alone would let it
        // shadow the resource that actually is.
        if (isset($resource['query']) || isset($resource['fragment'])) {
            return -1;
        }

        foreach (['scheme', 'host', 'port'] as $part) {
            if (($resource[$part] ?? null) !== ($endpoint[$part] ?? null)) {
                return -1;
            }
        }

        $resourcePath = rtrim($resource['path'] ?? '/', '/') . '/';
        $endpointPath = rtrim($endpoint['path'] ?? '/', '/') . '/';

        return str_starts_with($endpointPath, $resourcePath) ? strlen($resourcePath) : -1;
    }
}
