<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Attributes\Request\ResetPasswordRequestBody;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ResetPasswordController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly UserServiceInterface $userService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    #[Route('/user/reset-password', name: 'pimcore_studio_api_user_reset_password', methods: ['POST'])]
    #[Post(
        path: self::API_PATH . '/user/reset-password',
        operationId: 'reset-password',
        summary: 'Sending username to reset password',
        tags: [Tags::User->value]
    )]
    #[ResetPasswordRequestBody]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::TOO_MANY_REQUESTS,
    ])]
    public function resetPassword(#[MapRequestPayload] ResetPassword $resetPassword): Response
    {
        $this->userService->resetPassword($resetPassword);

        return new Response();
    }
}
