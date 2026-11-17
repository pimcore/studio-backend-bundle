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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\McpTokenServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class RevokeMcpTokensController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly McpTokenServiceInterface $mcpTokenService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        '/service-user/{id}/mcp-token',
        name: 'pimcore_studio_api_service_user_mcp_token_revoke',
        requirements: ['id' => '\d+'],
        methods: ['DELETE']
    )]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Delete(
        path: self::PREFIX . '/service-user/{id}/mcp-token',
        operationId: 'service_user_mcp_token_revoke',
        description: 'service_user_mcp_token_revoke_description',
        summary: 'service_user_mcp_token_revoke_summary',
        tags: [Tags::ServiceUser->value]
    )]
    #[IdParameter(type: 'service user')]
    #[SuccessResponse(description: 'service_user_mcp_token_revoke_success_response')]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function revokeMcpTokens(int $id): Response
    {
        $this->mcpTokenService->revokeTokens($id);

        return new Response();
    }
}
