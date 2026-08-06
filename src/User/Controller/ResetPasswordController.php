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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Attribute\Request\ResetPasswordRequestBody;
use Pimcore\Bundle\StudioBackendBundle\User\RateLimiter\RateLimiterInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserLoginServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ResetPasswordController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly UserLoginServiceInterface $loginService,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly RateLimiterFactoryInterface $resetPasswordLimiter,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    #[Route('/user/reset-password', name: 'pimcore_studio_api_user_reset_password', methods: ['POST'])]
    #[IsGranted(self::VOTER_PUBLIC_STUDIO_API, 'resetPassword')]
    #[Post(
        path: self::PREFIX . '/user/reset-password',
        operationId: 'user_reset_password',
        summary: 'user_reset_password_summary',
        tags: [Tags::User->value]
    )]
    #[ResetPasswordRequestBody]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::TOO_MANY_REQUESTS,
    ])]
    public function resetPassword(#[MapRequestPayload] ResetPassword $resetPassword): Response
    {
        $this->rateLimiter->check($this->resetPasswordLimiter);
        $this->loginService->resetPassword($resetPassword);

        return new Response();
    }
}
