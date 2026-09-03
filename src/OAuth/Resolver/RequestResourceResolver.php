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
    public function __construct(
        private ResourceRegistryInterface $resourceRegistry,
        private ?string $issuer = null,
    ) {
    }

    public function resolve(Request $request): ?ProtectedResource
    {
        // Resources are registered under the issuer, so the request has to be
        // expressed the same way. Behind a proxy the request host is not the
        // issuer, and comparing the two would refuse a correctly issued token.
        $base = rtrim($this->issuer ?? $request->getSchemeAndHttpHost(), '/');
        $target = CanonicalUri::canonicalize($base . $request->getPathInfo());

        $match = null;
        $matchLength = 0;
        foreach ($this->resourceRegistry->all() as $resource) {
            if (!self::covers($resource->canonicalUri, $target)) {
                continue;
            }

            // Longest match wins, so a resource registered for one server takes
            // precedence over a broader one registered for its whole prefix.
            $length = strlen($resource->canonicalUri);
            if ($length > $matchLength) {
                $match = $resource;
                $matchLength = $length;
            }
        }

        return $match;
    }

    /**
     * The rule a standards-based client applies when deciding whether a resource
     * covers an endpoint: same origin, and a path prefix that only matches on a
     * segment boundary, so `/pimcore-mcp/agent` never covers `/pimcore-mcp/agentx`.
     */
    private static function covers(string $resourceUri, string $target): bool
    {
        $resource = parse_url($resourceUri);
        $endpoint = parse_url($target);

        if (!is_array($resource) || !is_array($endpoint)) {
            return false;
        }

        foreach (['scheme', 'host', 'port'] as $part) {
            if (($resource[$part] ?? null) !== ($endpoint[$part] ?? null)) {
                return false;
            }
        }

        $resourcePath = rtrim($resource['path'] ?? '/', '/') . '/';
        $endpointPath = rtrim($endpoint['path'] ?? '/', '/') . '/';

        return str_starts_with($endpointPath, $resourcePath);
    }
}
