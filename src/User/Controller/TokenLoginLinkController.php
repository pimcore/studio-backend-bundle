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

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Attribute\Request\TokenLinkRequestBody;
use Pimcore\Bundle\StudioBackendBundle\User\Attribute\Response\TokenLinkJson;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TokenLink;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserLoginServiceInterface;
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
final class TokenLoginLinkController extends AbstractApiController
{
    private const string ROUTE = '/user/token-link/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserLoginServiceInterface $loginService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_user_token_link_get', methods: ['POST'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'user_token_link_get',
        description: 'user_token_link_get_description',
        summary: 'user_token_link_get_summary',
        tags: [Tags::User->value]
    )]
    #[IdParameter(type: 'user')]
    #[TokenLinkRequestBody]
    #[SuccessResponse(
        description: 'user_token_link_get_success_response',
        content: new TokenLinkJson(),
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getTokenLink(int $id, #[MapRequestPayload] TokenLink $parameter): JsonResponse
    {
        return $this->jsonResponse(['link' => $this->loginService->getLoginTokenUrl($id, $parameter)]);
    }
}
