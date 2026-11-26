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

namespace Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Schema\MaintenanceModeState;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Service\MaintenanceModeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ActivateController extends AbstractApiController
{
    private const string ROUTE = '/maintenance-mode';

    public function __construct(
        SerializerInterface $serializer,
        private readonly MaintenanceModeServiceInterface $maintenanceModeService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|NoRequestException
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_maintenance_mode_activate', methods: ['POST'])]
    #[IsGranted(UserPermissions::PIMCORE_ADMIN->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'maintenance_mode_activate',
        description: 'maintenance_mode_activate_description',
        summary: 'maintenance_mode_activate_summary',
        tags: [Tags::MaintenanceMode->value],
    )]
    #[SuccessResponse(
        description: 'maintenance_mode_activate_success_response',
        content: new JsonContent(ref: MaintenanceModeState::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function activateMaintenanceMode(): JsonResponse
    {
        return $this->jsonResponse($this->maintenanceModeService->activate());
    }
}
