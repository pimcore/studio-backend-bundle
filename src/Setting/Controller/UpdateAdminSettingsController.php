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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Attribute\Request\UpdateAdminSettingsRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\UpdateAdminSettings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\AdminSettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateAdminSettingsController extends AbstractApiController
{
    private const string ROUTE = '/settings/admin';

    public function __construct(
        SerializerInterface $serializer,
        private readonly AdminSettingsServiceInterface $adminSettingsService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_admin_settings_update', methods: ['POST'])]
    #[IsGranted(UserPermissions::SYSTEM_APPEARANCE_SETTINGS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'admin_settings_update',
        description: 'admin_settings_update_description',
        summary: 'admin_settings_update_summary',
        tags: [Tags::Settings->name]
    )]
    #[UpdateAdminSettingsRequestBody]
    #[SuccessResponse(
        description: 'admin_settings_update_success_response',
    )]
    #[DefaultResponses]
    public function updateAdminSettings(
        #[MapRequestPayload] UpdateAdminSettings $parameters
    ): Response {
        $this->adminSettingsService->updateAdminSettings($parameters);

        return new Response();
    }
}
