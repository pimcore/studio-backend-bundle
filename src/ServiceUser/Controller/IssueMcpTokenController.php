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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\McpTokenParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpToken;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\McpTokenServiceInterface;
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
final class IssueMcpTokenController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly McpTokenServiceInterface $mcpTokenService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|NotFoundException
     */
    #[Route(
        '/service-user/{id}/mcp-token',
        name: 'pimcore_studio_api_service_user_mcp_token_issue',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::PREFIX . '/service-user/{id}/mcp-token',
        operationId: 'service_user_mcp_token_issue',
        description: 'service_user_mcp_token_issue_description',
        summary: 'service_user_mcp_token_issue_summary',
        tags: [Tags::ServiceUser->value]
    )]
    #[IdParameter(type: 'service user')]
    #[RequestBody(content: new JsonContent(ref: McpTokenParameter::class))]
    #[SuccessResponse(
        description: 'service_user_mcp_token_issue_success_response',
        content: new JsonContent(ref: ServiceUserMcpToken::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function issueMcpToken(
        int $id,
        #[MapRequestPayload] McpTokenParameter $parameter
    ): JsonResponse {
        return $this->jsonResponse($this->mcpTokenService->issueToken($id, $parameter->getTtlDays()));
    }
}
