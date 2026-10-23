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
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Request\CredentialsRequestBody;
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
final class LoginController extends AbstractApiController
{
    #[Route('/login', name: 'pimcore_studio_api_login', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/login',
        operationId: 'login',
        description: 'login_description',
        summary: 'login_summary',
        tags: [Tags::Authorization->name]
    )]
    #[CredentialsRequestBody]
    #[SuccessResponse(
        description: 'login_success_response'
    )]
    #[InvalidCredentialsResponse]
    #[DefaultResponses]
    public function login(): Response
    {
        return new Response();
    }
}
