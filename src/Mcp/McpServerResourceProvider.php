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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ProtectedResource;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Util\CanonicalUri;
use function preg_match;

/**
 * Declares every enabled MCP server managed through Studio as its own OAuth protected
 * resource, `<issuer>/pimcore-mcp/studio/<slug>`. The resource server holds a token to
 * the most specific resource covering the request, so a token requested for one server
 * is refused at every other one, rather than opening all of them through the shared
 * `/pimcore-mcp` base that {@see ProtectedResourceProvider} declares.
 *
 * Read from the server configuration when the registry is first consulted, not at
 * container build: servers created or changed through the Studio API are then covered
 * too, not only those shipped in the `studio_mcp_servers` configuration. The registry
 * memoises what it read for the life of the process. Under PHP-FPM that is one request;
 * a long-running worker keeps the servers it saw first until it is restarted.
 *
 * The URI is built from the configured `oauth.issuer`, never from a request, for the
 * reason {@see ProtectedResourceProvider} gives. Without an issuer, or with OAuth off,
 * nothing is contributed, which fails closed.
 *
 * @internal
 */
final readonly class McpServerResourceProvider implements ProtectedResourceProviderInterface
{
    public function __construct(
        private McpServerConfigRepositoryInterface $repository,
        private McpToolRegistryInterface $toolRegistry,
        private bool $enabled = false,
        private ?string $issuer = null,
    ) {
    }

    public function resources(): iterable
    {
        if (!$this->enabled || $this->issuer === null) {
            return;
        }

        try {
            $servers = $this->repository->list();
        } catch (NotFoundException) {
            // A server listed but not loadable is a storage problem, not a reason to take
            // down every other protected resource with it. Its server stays unregistered,
            // which refuses OAuth for it.
            return;
        }

        foreach ($servers as $server) {
            // A disabled server is not served, and a slug the route does not accept can
            // never be reached; advertising either would name an audience nothing honours.
            if (!$server->enabled || preg_match(McpPath::STUDIO_SLUG_PATTERN, $server->urlSlug) !== 1) {
                continue;
            }

            yield new ProtectedResource(
                CanonicalUri::canonicalize($this->issuer . McpPath::STUDIO . '/' . $server->urlSlug),
                McpScopes::forServer($server, $this->toolRegistry),
                [$this->issuer],
            );
        }
    }
}
