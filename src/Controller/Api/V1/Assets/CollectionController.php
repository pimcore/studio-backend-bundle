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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api\V1\Assets;

use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Dto\Collection;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQuery;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CollectionController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetQueryProviderInterface $assetQueryProvider,
        private readonly AssetSearchServiceInterface $assetSearchService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/v1/assets', name: 'pimcore_studio_api_v1_asets', methods: ['GET'])]
    public function getAssets(#[MapQueryString] Collection $collection): JsonResponse
    {

        $assetQuery = $this->getAssetQuery()
            ->setPage($collection->getPage())
            ->setPageSize($collection->getPageSize());

        return $this->jsonLd($this->assetSearchService->searchAssets($assetQuery));
    }

    private function getAssetQuery(): AssetQuery
    {
        return $this->assetQueryProvider->createAssetQuery();
    }
}
