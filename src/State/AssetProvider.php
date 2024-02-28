<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Pimcore\Bundle\StudioApiBundle\Filter\AssetIdSearchFilter;
use Pimcore\Bundle\StudioApiBundle\Filter\AssetParentIdFilter;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;

final readonly class AssetProvider implements ProviderInterface
{
    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private Pagination $pagination
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $searchResult = $this->assetSearchService->searchAssets(
                $this->pagination->getPage($context),
                $this->pagination->getLimit($operation, $context),
                $context[AssetIdSearchFilter::ASSET_ID_SEARCH_FILTER] ?? null,
                $context[AssetParentIdFilter::ASSET_PARENT_ID_FILTER_CONTEXT] ?? null,
            );

            return new TraversablePaginator(
                new ArrayIterator($searchResult->getItems()),
                $this->pagination->getPage($context),
                $this->pagination->getLimit($operation, $context),
                $searchResult->getTotalItems()
            );
        }
        return $this->assetSearchService->getAssetById($uriVariables['id']);
    }
}
