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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorSetup;
use Pimcore\Bundle\StudioBackendBundle\User\Service\TwoFactorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SetupController extends AbstractApiController
{
    public const string ROUTE_NAME = 'pimcore_studio_api_user_two_factor_setup';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TwoFactorServiceInterface $twoFactorService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/user/two-factor/setup', name: self::ROUTE_NAME, methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/user/two-factor/setup',
        operationId: 'user_two_factor_setup',
        description: 'user_two_factor_setup_description',
        summary: 'user_two_factor_setup_summary',
        tags: [Tags::User->value]
    )]
    #[SuccessResponse(
        description: 'user_two_factor_setup_success_response',
        content: new JsonContent(ref: TwoFactorSetup::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function setup(#[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponse($this->twoFactorService->createSetup($user->getUser()));
    }
}
