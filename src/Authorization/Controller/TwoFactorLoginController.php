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
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Request\TwoFactorCodeRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnreachableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class TwoFactorLoginController extends AbstractApiController
{
    public const string ROUTE_NAME = 'pimcore_studio_api_login_2fa';

    #[Route('/login/2fa', name: self::ROUTE_NAME, methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/login/2fa',
        operationId: 'login_two_factor',
        description: 'login_2fa_description',
        summary: 'login_2fa_summary',
        tags: [Tags::Authorization->name]
    )]
    #[TwoFactorCodeRequestBody]
    #[SuccessResponse(
        description: 'login_2fa_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::TOO_MANY_REQUESTS,
    ])]
    public function verify(): void
    {
        throw new UnreachableException('Should not be called. Handled by the two-factor authenticator.');
    }
}
