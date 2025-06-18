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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSetting;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Schema\WebsiteSettingsAdd;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Service\WebsiteSettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AddController extends AbstractApiController
{
    private const string ROUTE = '/website-settings/add';

    public function __construct(
        private readonly WebsiteSettingsServiceInterface $websiteSettingsService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_website_settings_add', methods: ['POST'])]
    #[IsGranted(UserPermissions::WEBSITE_SETTINGS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'website_settings_add',
        description: 'website_settings_add_description',
        summary: 'website_settings_add_summary',
        tags: [Tags::WebsiteSettings->value]
    )]
    #[SuccessResponse(
        description: 'website_settings_add_success_response',
        content: new JsonContent(ref: WebsiteSetting::class)
    )]
    #[ReferenceRequestBody(WebsiteSettingsAdd::class)]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function addWebsiteSetting(
        #[MapRequestPayload] WebsiteSettingsAdd $parameters
    ): JsonResponse {
        return $this->jsonResponse($this->websiteSettingsService->addWebsiteSetting($parameters));
    }
}
