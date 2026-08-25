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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Controller;

use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Server\McpServerFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function sprintf;

/**
 * Serves a configured MCP server over HTTP at /pimcore-mcp/studio/{server}, under
 * the shared pimcore_mcp firewall (so it accepts the OAuth bearer). Resolves the
 * definition by URL slug, enforces per-server access, assembles the server from
 * its tools and runs the streamable-HTTP transport.
 *
 * Routed explicitly in config/pimcore/routing.yaml (not by attribute) so it is
 * not swept under the Studio API url prefix.
 *
 * @internal
 */
#[IsGranted('ROLE_PIMCORE_USER')]
final readonly class McpServerController
{
    public function __construct(
        private McpServerConfigRepositoryInterface $serverRepository,
        private McpServerFactoryInterface $serverFactory,
        private McpServerAccessResolverInterface $accessResolver,
        private SecurityServiceInterface $securityService,
        private HttpMessageFactoryInterface $httpMessageFactory,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, string $server): Response
    {
        $definition = $this->resolveServer($server);

        if (!$this->accessResolver->isAllowed($definition, $this->securityService->getCurrentUser())) {
            throw new AccessDeniedHttpException(
                sprintf('You are not allowed to use the MCP server "%s".', $server)
            );
        }

        // An explicit middleware stack is passed because the SDK's default also installs
        // DnsRebindingProtectionMiddleware, which literally matches Host/Origin against
        // {localhost, 127.0.0.1, [::1]} and 403s anything else — incompatible with a
        // reverse-proxy topology. Host validation is Pimcore's TRUSTED_HOSTS instead,
        // enforced in kernel.request before this controller runs.
        $transport = new StreamableHttpTransport(
            request: $this->httpMessageFactory->createRequest($request),
            responseFactory: $this->responseFactory,
            streamFactory: $this->streamFactory,
            logger: $this->logger,
            middleware: [
                new CorsMiddleware(),
                new ProtocolVersionMiddleware(),
            ],
        );

        $response = $this->serverFactory->createServer($definition)->run($transport);

        return $this->httpFoundationFactory->createResponse($response);
    }

    private function resolveServer(string $slug): McpServerDefinition
    {
        foreach ($this->serverRepository->list() as $definition) {
            if ($definition->urlSlug === $slug && $definition->enabled) {
                return $definition;
            }
        }

        throw new NotFoundHttpException(sprintf('MCP server "%s" not found.', $slug));
    }
}
