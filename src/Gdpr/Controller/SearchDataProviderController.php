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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\GdprRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter\GdprStructuredSearchParameters;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResultProperty;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SearchDataProviderController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GdprManagerServiceInterface $gdprManagerService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * Handles GDPR data search requests across different providers.
     *
     * @throws NotFoundException
     */
    #[Route(
        '/gdpr/search',
        name: 'pimcore_studio_api_gdpr_search',
        methods: ['POST'])]
    #[IsGranted(UserPermissions::GDPR->value)]
    #[POST(
        path: self::PREFIX . '/gdpr/search',
        operationId: 'gdpr_search_data',
        description: 'gdpr_search_data_description',
        summary: 'gdpr_search_data_summary',
        tags: [Tags::GDPR->value]
    )]
    #[GdprRequestBody]
    #[SuccessResponse(
        description: 'gdpr_search_data_success_response',
        content: new CollectionJson(
            collection: new GdprSearchResultProperty()
        )
    )]

    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function searchData(
        #[MapRequestPayload] GdprStructuredSearchParameters $request
    ): JsonResponse {

        $collection = $this->gdprManagerService->search($request);

        return $this->jsonResponse($collection);
    }
}
