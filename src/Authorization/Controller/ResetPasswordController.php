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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attributes\Request\CredentialsRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attributes\Request\ResetPasswordRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attributes\Request\TokenRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\Credentials;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\Refresh;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\Token;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Service\TokenServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ResetPasswordController extends AbstractApiController
{
    public function __construct(
       private readonly UserServiceInterface $userService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    #[Route('/reset-password', name: 'pimcore_studio_api_reset_password', methods: ['POST'])]
    #[Post(
        path: self::API_PATH . '/reset-password',
        operationId: 'rest-password',
        summary: 'Sending username to reset password',
        tags: [Tags::Authorization->name]
    )]
    #[ResetPasswordRequestBody]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::TOO_MANY_REQUESTS
    ])]
    public function resetPassword(#[MapRequestPayload] ResetPassword $resetPassword): JsonResponse
    {
        $this->userService->resetPassword($resetPassword);
        return new JsonResponse();
    }
}
