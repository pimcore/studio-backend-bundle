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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Service\TwoFactorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DisableController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly TwoFactorServiceInterface $twoFactorService
    ) {
        parent::__construct($serializer);
    }

    /**
     * Priority: DELETE /user/{id} has no id requirement and would match this path otherwise.
     *
     * @throws ForbiddenException|DatabaseException
     */
    #[Route('/user/two-factor', name: 'pimcore_studio_api_user_two_factor_disable', methods: ['DELETE'], priority: 1)]
    #[Delete(
        path: self::PREFIX . '/user/two-factor',
        operationId: 'user_two_factor_disable',
        description: 'user_two_factor_disable_description',
        summary: 'user_two_factor_disable_summary',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'user_two_factor_disable_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
    ])]
    public function disable(#[CurrentUser] User $user): Response
    {
        $this->twoFactorService->disable($user->getUser());

        return new Response();
    }
}
