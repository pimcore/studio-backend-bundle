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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Request\TokenRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Response\InvalidCredentialsResponse;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class TokenLoginController extends AbstractApiController
{
    private const string ROUTE = '/login/token';

    public const string ROUTE_NAME = 'pimcore_studio_api_token_login';

    #[Route(self::ROUTE, name: self::ROUTE_NAME, methods: ['POST'])]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'login_token',
        description: 'login_token_description',
        summary: 'login_token_summary',
        tags: [Tags::Authorization->name]
    )]
    #[TokenRequestBody]
    #[SuccessResponse(
        description: 'login_token_success_response'
    )]
    #[InvalidCredentialsResponse]
    #[DefaultResponses]
    public function tokenLogin(): Response
    {
        return new Response();
    }
}
