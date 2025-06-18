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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Service\WebsiteSettingsServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/website-settings/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WebsiteSettingsServiceInterface $websiteSettingsService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_website_settings_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::WEBSITE_SETTINGS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'website_settings_delete',
        description: 'website_settings_delete_description',
        summary: 'website_settings_delete_summary',
        tags: [Tags::WebsiteSettings->value]
    )]
    #[IdParameter(type: 'website setting')]
    #[SuccessResponse(
        description: 'website_settings_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteWebsiteSetting(int $id): Response
    {
        $this->websiteSettingsService->deleteWebsiteSetting($id);

        return new Response();
    }
}
