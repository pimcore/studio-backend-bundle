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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Controller\Server;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpServerConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class ListServersController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/mcp/servers';

    public function __construct(
        SerializerInterface $serializer,
        private readonly McpServerConfigurationServiceInterface $mcpServerConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    // No permission gate: any authenticated user may list, but the service returns
    // only the servers they have at least read access to (deny-by-default).
    #[Route(self::ROUTE, name: 'pimcore_studio_api_get_mcp_servers', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'mcp_get_servers',
        description: 'mcp_get_servers_description',
        summary: 'mcp_get_servers_summary',
        tags: [Tags::Mcp->value]
    )]
    #[SuccessResponse(
        description: 'mcp_get_servers_success_response',
        content: new CollectionJson(new GenericCollection(McpServer::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getMcpServers(): JsonResponse
    {
        $servers = $this->mcpServerConfigurationService->listConfigurations();

        return $this->getPaginatedCollection($this->serializer, $servers, count($servers));
    }
}
