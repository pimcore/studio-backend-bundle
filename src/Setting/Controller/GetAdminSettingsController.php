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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\AdminSettings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\AdminSettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetAdminSettingsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const ROUTE = '/settings/admin';

    public function __construct(
        SerializerInterface $serializer,
        private readonly AdminSettingsServiceInterface $adminSettingsService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_admin_settings', methods: ['GET'])]
    #[IsGranted(UserPermissions::SYSTEM_APPEARANCE_SETTINGS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'admin_settings_get',
        description: 'admin_settings_get_description',
        summary: 'admin_settings_get_summary',
        tags: [Tags::Settings->name]
    )]
    #[SuccessResponse(
        description: 'admin_settings_get_success_response',
        content: new JsonContent(ref: AdminSettings::class)
    )]
    #[DefaultResponses]
    public function getAdminSettings(): JsonResponse
    {
        return $this->jsonResponse($this->adminSettingsService->getAdminSettings());
    }
}
