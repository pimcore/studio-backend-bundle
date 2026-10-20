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

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller\TwoFactor;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Attribute\Request\TwoFactorCodeRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\TwoFactorCode;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Service\TwoFactorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ConfirmController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TwoFactorServiceInterface $twoFactorService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ConflictException|UnprocessableContentException|DatabaseException
     */
    #[Route('/user/two-factor/confirm', name: 'pimcore_studio_api_user_two_factor_confirm', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/user/two-factor/confirm',
        operationId: 'user_two_factor_confirm',
        description: 'user_two_factor_confirm_description',
        summary: 'user_two_factor_confirm_summary',
        tags: [Tags::User->value]
    )]
    #[TwoFactorCodeRequestBody]
    #[SuccessResponse(
        description: 'user_two_factor_confirm_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::CONFLICT,
    ])]
    public function confirm(#[CurrentUser] User $user, #[MapRequestPayload] TwoFactorCode $code): Response
    {
        $this->twoFactorService->confirmSetup($user->getUser(), $code->getCode());

        return new Response();
    }
}
