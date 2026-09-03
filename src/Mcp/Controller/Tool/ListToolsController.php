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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Controller\Tool;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpToolItem;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpToolCatalogueServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class ListToolsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/mcp/tools';

    public function __construct(
        SerializerInterface $serializer,
        private readonly McpToolCatalogueServiceInterface $mcpToolCatalogueService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_get_mcp_tools', methods: ['GET'])]
    #[IsGranted(UserPermissions::MCP_SERVERS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'mcp_get_tools',
        description: 'mcp_get_tools_description',
        summary: 'mcp_get_tools_summary',
        tags: [Tags::Mcp->value]
    )]
    #[SuccessResponse(
        description: 'mcp_get_tools_success_response',
        content: new CollectionJson(new GenericCollection(McpToolItem::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getMcpTools(): JsonResponse
    {
        $tools = $this->mcpToolCatalogueService->listTools();

        return $this->getPaginatedCollection($this->serializer, $tools, count($tools));
    }
}
