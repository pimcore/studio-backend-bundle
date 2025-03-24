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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserUpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateActivePerspectiveController extends AbstractApiController
{
    private const string ROUTE = '/user/active-perspective/{perspectiveId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly UserUpdateServiceInterface $userUpdateService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException|NotWriteableException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_user_active_perspective_update', methods: ['PUT'])]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'user_update_active_perspective',
        summary: 'user_update_active_perspective_summary',
        tags: [Tags::User->value]
    )]
    #[StringParameter('perspectiveId', 'd061699e_da42_4075_b504_c2c93c687819', 'Set active perspective by Id')]
    #[SuccessResponse(description: 'user_update_active_perspective_success_response')]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateActivePerspective(string $perspectiveId): Response
    {
        $this->userUpdateService->updateActivePerspective($perspectiveId);

        return new Response();
    }
}
