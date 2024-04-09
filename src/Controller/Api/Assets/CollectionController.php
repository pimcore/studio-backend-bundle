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
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Controller\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Collection;
use Pimcore\Bundle\StudioApiBundle\Dto\Unauthorized;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQuery;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetQueryProviderInterface $assetQueryProvider,
        private readonly AssetSearchServiceInterface $assetSearchService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets', name: 'pimcore_studio_api_assets', methods: ['GET'])]
    #[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/assets',
        description: 'Get paginated assets',
        summary: 'Get all assets',
        security: [
            [
                'auth_token' => [],
            ],
        ],
        tags: ['Assets']
    )]
    #[QueryParameter(
        name: 'page',
        description: 'Page number',
        in: 'query',
        required: true,
        schema: new Schema(type: 'integer', example: 1),
        example: 1
    )]
    #[QueryParameter(
        name: 'limit',
        description: 'Number of items per page',
        in: 'query',
        required: true,
        schema: new Schema(type: 'integer', example: 1),
        example: 10
    )]
    #[Response(
        response: 200,
        description: 'Paginated assets with total count as header param',
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Asset::class, type: 'object')
            ),
        ]
    )]
    #[Response(
        response: 403,
        description: 'Unauthorized',
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Unauthorized::class, type: 'object')
            ),
        ]
    )]
    public function getAssets(#[MapQueryString] Collection $collection): JsonResponse
    {

        $assetQuery = $this->getAssetQuery()
            ->setPage($collection->getPage())
            ->setPageSize($collection->getLimit());
        $result = $this->assetSearchService->searchAssets($assetQuery);

        return $this->getPaginatedCollection(
            $this->serializer,
            $result->getItems(),
            $result->getTotalItems()
        );
    }

    private function getAssetQuery(): AssetQuery
    {
        return $this->assetQueryProvider->createAssetQuery();
    }
}
