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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ExcludeFoldersParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\IdSearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PathIncludeDescendantsParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PathIncludeParentParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\BadRequestResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Property\AnyOfAsset;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Provider\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetSearchServiceInterface $assetSearchService,
        private readonly FilterServiceProviderInterface $filterServiceProvider
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidQueryTypeException
     */
    #[Route('/assets', name: 'pimcore_studio_api_assets', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/assets',
        operationId: 'getAssets',
        description: 'Get paginated assets',
        summary: 'Get all assets',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Assets->name]
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[ParentIdParameter]
    #[IdSearchTermParameter]
    #[ExcludeFoldersParameter]
    #[PathParameter]
    #[PathIncludeParentParameter]
    #[PathIncludeDescendantsParameter]
    #[SuccessResponse(
        description: 'Paginated assets with total count as header param',
        content: new CollectionJson(new AnyOfAsset())
    )]
    #[BadRequestResponse]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getAssets(#[MapQueryString] ElementParameters $parameters): JsonResponse
    {
        $filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);

        $assetQuery = $filterService->applyFilters(
            $parameters,
            OpenSearchFilterInterface::TYPE_ASSET
        );

        $result = $this->assetSearchService->searchAssets($assetQuery);

        return $this->getPaginatedCollection(
            $this->serializer,
            $result->getItems(),
            $result->getTotalItems()
        );
    }
}
