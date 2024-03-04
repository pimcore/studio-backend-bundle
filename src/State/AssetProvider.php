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
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryContextTrait;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;

final readonly class AssetProvider implements ProviderInterface
{
    use AssetQueryContextTrait;

    public function __construct(
        AssetQueryProviderInterface $assetQueryProvider,
        private AssetSearchServiceInterface $assetSearchService,
        private Pagination $pagination
    ) {
        $this->assetQueryProvider = $assetQueryProvider;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {

            $assetQuery = $this->getAssetQuery($context)
                ->setPage($this->pagination->getPage($context))
                ->setPageSize($this->pagination->getLimit($operation, $context));

            $searchResult = $this->assetSearchService->searchAssets($assetQuery);

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
