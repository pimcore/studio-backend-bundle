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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Listing\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Mapper\CollectionParametersMapper;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\PaginationFilter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperService;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Model\Element\Note\Listing as NoteListing;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Pagination only reaches a listing as the `page` / `page.size` column filters the mapper adds,
 * and `PaginationFilter` leaves a listing untouched when either of them is missing. An omitted
 * `filters` therefore has to be mapped like any other request, with the FilterParameter defaults,
 * or a request body of `{}` answers with every row in the table.
 *
 * @internal
 */
final class FilterMapperServiceTest extends Unit
{
    private const int DEFAULT_PAGE = 1;

    private const int DEFAULT_PAGE_SIZE = 50;

    public function testAnOmittedFiltersObjectStillCarriesTheDefaultPaging(): void
    {
        $parameters = $this->createService()->getFilterParameters(new CollectionFilterParameter());

        $page = $parameters->getFirstColumnFilterByType(FilterType::PAGE->value);
        $pageSize = $parameters->getFirstColumnFilterByType(FilterType::PAGE_SIZE->value);

        $this->assertNotNull($page, 'An omitted `filters` still has to produce a page column.');
        $this->assertNotNull($pageSize, 'An omitted `filters` still has to produce a page size column.');
        $this->assertSame(self::DEFAULT_PAGE, $page->getFilterValue());
        $this->assertSame(self::DEFAULT_PAGE_SIZE, $pageSize->getFilterValue());
    }

    /**
     * The end of the chain: the mapped parameters have to leave the listing limited.
     */
    public function testAnEmptyRequestBodyLimitsTheListing(): void
    {
        $listing = new NoteListing();

        (new PaginationFilter())->apply(
            $this->createService()->getFilterParameters(new CollectionFilterParameter()),
            $listing
        );

        $this->assertSame(self::DEFAULT_PAGE_SIZE, $listing->getLimit());
        $this->assertSame(0, $listing->getOffset());
    }

    /**
     * The other direction: a body that does send paging still gets exactly what it asked for.
     */
    public function testAnExplicitPageAndPageSizeAreUnchanged(): void
    {
        $listing = new NoteListing();

        (new PaginationFilter())->apply(
            $this->createService()->getFilterParameters(
                new CollectionFilterParameter(new FilterParameter(page: 3, pageSize: 10))
            ),
            $listing
        );

        $this->assertSame(10, $listing->getLimit());
        $this->assertSame(20, $listing->getOffset());
    }

    /**
     * An omitted `filters` must not lose the rest of the parameter set either - the sort filter
     * a listing orders by comes from the same mapping.
     */
    public function testAnOmittedFiltersObjectKeepsTheDefaultSortFilter(): void
    {
        $parameters = $this->createService()->getFilterParameters(new CollectionFilterParameter());

        $this->assertSame(
            (new FilterParameter())->getSortFilter()->getKey(),
            $parameters->getSortFilter()->getKey()
        );
    }

    private function createService(): FilterMapperService
    {
        return new FilterMapperService(
            new ServiceLocator([
                CollectionFilterParameter::class => static fn (): CollectionParametersMapper
                    => new CollectionParametersMapper(),
            ])
        );
    }
}
