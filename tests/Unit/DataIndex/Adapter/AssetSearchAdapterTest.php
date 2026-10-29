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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Adapter;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResult as GdiAssetSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Paging\PaginationInfo;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\Aggregation\FileSizeAggregationServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\SearchHelper;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchResultIdListServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\AssetSearchAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator\AssetHydratorServiceInterface;
use ReflectionClass;

/**
 * @internal
 */
final class AssetSearchAdapterTest extends Unit
{
    public function testSearchResultsCarryTheScore(): void
    {
        $items = [$this->item(0.63), $this->item(null)];
        $searchService = $this->makeEmpty(AssetSearchServiceInterface::class, [
            'search' => new GdiAssetSearchResult($items, new PaginationInfo(2, 1, 10, 1)),
        ]);
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'getSearch' => $this->makeEmpty(AssetSearchInterface::class),
        ]);

        $result = $this->adapter($searchService)->searchAssets($query)->getItems();

        $this->assertSame(0.63, $result[0]->getScore());
        $this->assertNull($result[1]->getScore());
    }

    public function testTheDetailIsNotScored(): void
    {
        $searchService = $this->makeEmpty(AssetSearchServiceInterface::class, [
            'byId' => $this->item(0.63),
        ]);

        $this->assertNull($this->adapter($searchService)->getAssetById(1)->getScore());
    }

    private function item(?float $score): AssetSearchResultItem
    {
        return (new AssetSearchResultItem())->setScore($score);
    }

    private function adapter(AssetSearchServiceInterface $searchService): AssetSearchAdapter
    {
        $hydratorService = $this->makeEmpty(AssetHydratorServiceInterface::class, [
            'hydrateAssets' => fn (): Asset => $this->getMockBuilder(Asset::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock(),
        ]);

        return new AssetSearchAdapter(
            $searchService,
            $hydratorService,
            // Final GDI helper, not used by the tested paths.
            (new ReflectionClass(SearchHelper::class))->newInstanceWithoutConstructor(),
            $this->makeEmpty(SearchResultIdListServiceInterface::class),
            $this->makeEmpty(FileSizeAggregationServiceInterface::class),
        );
    }
}
