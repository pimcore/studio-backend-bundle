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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSetting\Service\WebsiteSettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListTypeController extends AbstractApiController
{
    private const string ROUTE = '/website-settings/types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WebsiteSettingsServiceInterface $websiteSettingsService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_website_settings_list_types', methods: ['GET'])]
    #[IsGranted(UserPermissions::WEBSITE_SETTINGS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'website_settings_list_types',
        description: 'website_settings_list_types_description',
        summary: 'website_settings_list_types_summary',
        tags: [Tags::WebsiteSettings->value]
    )]
    #[SuccessResponse(
        description: 'website_settings_list_types_success_response',
        content: new ItemsJson(DocType::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listTypes(): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->websiteSettingsService->listTypes()]);
    }
}
