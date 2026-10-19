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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Attribute\Request\McpServerRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Service\McpServerConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateServerController extends AbstractApiController
{
    private const string ROUTE = '/mcp/servers';

    public function __construct(
        SerializerInterface $serializer,
        private readonly McpServerConfigurationServiceInterface $mcpServerConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementExistsException|ElementSavingFailedException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_create_mcp_server', methods: ['POST'])]
    #[IsGranted(UserPermissions::MCP_SERVERS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'mcp_create_server',
        description: 'mcp_create_server_description',
        summary: 'mcp_create_server_summary',
        tags: [Tags::Mcp->value]
    )]
    #[McpServerRequestBody]
    #[SuccessResponse(
        description: 'mcp_create_server_success_response',
        content: new JsonContent(ref: McpServer::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createMcpServer(
        #[MapRequestPayload] McpServerParameter $parameter
    ): JsonResponse {
        return $this->jsonResponse(
            $this->mcpServerConfigurationService->saveConfiguration($parameter)
        );
    }
}
