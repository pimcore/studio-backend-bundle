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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema\WebsiteSetting;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema\WebsiteSettingsUpdate;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Service\WebsiteSettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/website-settings/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WebsiteSettingsServiceInterface $websiteSettingsService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_website_settings_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::WEBSITE_SETTINGS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'website_settings_update',
        description: 'website_settings_update_description',
        summary: 'website_settings_update_summary',
        tags: [Tags::WebsiteSettings->value]
    )]
    #[IdParameter(type: 'website setting')]
    #[ReferenceRequestBody(WebsiteSettingsUpdate::class)]
    #[SuccessResponse(
        description: 'website_settings_update_success_response',
        content: new JsonContent(ref: WebsiteSetting::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateWebsiteSetting(
        int $id,
        #[MapRequestPayload] WebsiteSettingsUpdate $parameters
    ): JsonResponse {

        return $this->jsonResponse($this->websiteSettingsService->updateWebsiteSetting($id, $parameters));
    }
}
