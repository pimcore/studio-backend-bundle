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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResult as GdiDataObjectSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Paging\PaginationInfo;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\SearchHelper;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchResultIdListServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\DataObjectSearchAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator\DataObjectHydratorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use ReflectionClass;

/**
 * @internal
 */
final class DataObjectSearchAdapterTest extends Unit
{
    public function testSearchResultsCarryTheScore(): void
    {
        $items = [$this->item(0.42), $this->item(null)];
        $searchService = $this->makeEmpty(DataObjectSearchServiceInterface::class, [
            'search' => new GdiDataObjectSearchResult($items, new PaginationInfo(2, 1, 10, 1)),
        ]);
        $query = $this->makeEmpty(QueryInterface::class, [
            'getSearch' => $this->makeEmpty(DataObjectSearchInterface::class),
        ]);

        $result = $this->adapter($searchService)->searchDataObjects($query)->getItems();

        $this->assertSame(0.42, $result[0]->getScore());
        $this->assertNull($result[1]->getScore());
    }

    private function item(?float $score): DataObjectSearchResultItem
    {
        return (new DataObjectSearchResultItem())->setScore($score);
    }

    private function adapter(DataObjectSearchServiceInterface $searchService): DataObjectSearchAdapter
    {
        $hydratorService = $this->makeEmpty(DataObjectHydratorServiceInterface::class, [
            'hydrateDataObjects' => fn (): DataObject => $this->getMockBuilder(DataObject::class)
                ->disableOriginalConstructor()
                ->onlyMethods([])
                ->getMock(),
        ]);

        return new DataObjectSearchAdapter(
            $searchService,
            $hydratorService,
            // Final GDI helper, not used by the tested path.
            (new ReflectionClass(SearchHelper::class))->newInstanceWithoutConstructor(),
            $this->makeEmpty(SearchResultIdListServiceInterface::class),
        );
    }
}
