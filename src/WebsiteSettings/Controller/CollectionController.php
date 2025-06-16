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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema\WebsiteSetting;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Service\WebsiteSettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/website-settings';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WebsiteSettingsServiceInterface $websiteSettingsService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_website_settings_collection', methods: ['POST'])]
    #[IsGranted(UserPermissions::WEBSITE_SETTINGS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'website_settings_get_collection',
        description: 'website_settings_get_collection_description',
        summary: 'website_settings_get_collection_summary',
        tags: [Tags::WebsiteSettings->value]
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
            '{"key":"creationDate", "type":"date", "filterValue":{"operator": "on", "value": "08/20/2024"}},' .
            '{"key":"name", "type":"like", "filterValue": "SettingsName"},' .
            '{"key":"type", "type":"equals", "filterValue": "text"}'
            . ']',
        sortFilterExample: '{"key":"name", "direction":"DESC"}'
    )]
    #[SuccessResponse(
        description: 'website_settings_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(WebsiteSetting::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getWebsiteSettings(
        #[MapRequestPayload] CollectionFilterParameter $parameters
    ): JsonResponse {
        $collection = $this->websiteSettingsService->listWebsiteSettings($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
