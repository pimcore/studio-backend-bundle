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

use OpenApi\Attributes\Delete;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpServerConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteServerController extends AbstractApiController
{
    private const string ROUTE = '/mcp/servers/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly McpServerConfigurationServiceInterface $mcpServerConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException|NotWriteableException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_delete_mcp_server',
        requirements: ['id' => '[a-z0-9-]+'],
        methods: ['DELETE'],
    )]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'mcp_delete_server',
        description: 'mcp_delete_server_description',
        summary: 'mcp_delete_server_summary',
        tags: [Tags::Mcp->value]
    )]
    #[IdParameter(type: 'MCP server', schema: new Schema(type: 'string', example: 'product-read'))]
    #[SuccessResponse(
        description: 'mcp_delete_server_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteMcpServer(string $id): Response
    {
        $this->mcpServerConfigurationService->deleteConfiguration($id);

        return new Response();
    }
}
