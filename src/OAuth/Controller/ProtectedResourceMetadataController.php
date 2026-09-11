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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Controller;

use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ResourceRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the RFC 9728 Protected Resource Metadata document.
 *
 * The metadata URL is the resource URI with `/.well-known/oauth-protected-resource`
 * inserted, so the path suffix identifies the resource: a request for
 * `/.well-known/oauth-protected-resource/pimcore-mcp` describes the resource at
 * `<scheme-host>/pimcore-mcp`. Unknown/unregistered resources yield 404.
 *
 * @internal
 */
final class ProtectedResourceMetadataController
{
    public function __construct(
        private readonly ResourceRegistryInterface $resourceRegistry,
    ) {
    }

    public function __invoke(Request $request, string $resourcePath = ''): JsonResponse
    {
        $resourceUri = CanonicalUri::canonicalize($request->getSchemeAndHttpHost() . $resourcePath);
        $metadata = $this->resourceRegistry->metadataFor($resourceUri);

        if ($metadata === null) {
            return new JsonResponse(['error' => 'unknown_resource'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($metadata->toArray());
    }
}
