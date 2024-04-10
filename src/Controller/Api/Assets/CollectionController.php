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
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\LimitParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Controller\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Collection;
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
        security: self::SECURITY_SCHEME,
        tags: ['Assets'],
    )]
    #[PageParameter]
    #[LimitParameter]
    #[SuccessResponse(
        description: 'Paginated assets with total count as header param',
        content: new JsonContent(ref: Asset::class)
    )]
    #[UnauthorizedResponse]
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
