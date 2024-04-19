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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api\Assets;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\ExcludeFoldersParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\IdSearchTermParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\ParentIdParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PathIncludeDescendantsParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PathIncludeParentParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PathParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Property\AnyOfAsset;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Config\Tags;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Controller\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioApiBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\Parameters;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterServiceInterface;
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
        private readonly FilterServiceInterface $filterService
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
    #[UnauthorizedResponse]
    public function getAssets(#[MapQueryString] Parameters $parameters): JsonResponse
    {
        $assetQuery = $this->filterService->applyFilters(
            $parameters,
            FilterServiceInterface::TYPE_ASSET
        );

        $result = $this->assetSearchService->searchAssets($assetQuery);

        return $this->getPaginatedCollection(
            $this->serializer,
            $result->getItems(),
            $result->getTotalItems()
        );
    }
}
