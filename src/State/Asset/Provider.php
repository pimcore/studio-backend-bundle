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

namespace Pimcore\Bundle\StudioApiBundle\State\Asset;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryContextTrait;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;

final readonly class Provider implements ProviderInterface
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
