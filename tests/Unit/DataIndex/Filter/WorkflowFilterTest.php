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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\WorkflowFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * @internal
 */
final class WorkflowFilterTest extends Unit
{
    private MockObject|WorkflowElementsRepositoryInterface $repository;

    private WorkflowFilter $filter;

    protected function _before(): void
    {
        $this->repository = $this->createMock(WorkflowElementsRepositoryInterface::class);
        $this->filter = new WorkflowFilter($this->repository, $this->createMock(LoggerInterface::class));
    }

    public function testItIgnoresParametersThatAreNotColumnFilters(): void
    {
        $query = $this->createMock(DataObjectQueryInterface::class);
        $query->expects($this->never())->method('searchByIds');
        $this->repository->expects($this->never())->method('fetchByWorkflowState');

        self::assertSame($query, $this->filter->apply(new stdClass(), $query));
    }

    public function testItResolvesObjectIdsForTheWorkflowPlaceAndSearchesByThem(): void
    {
        $query = $this->createMock(DataObjectQueryInterface::class);

        // Data-object query -> element type 'data-object' passed to the repository.
        $this->repository->expects($this->once())
            ->method('fetchByWorkflowState')
            ->with('product_workflow', 'in_review', ElementTypes::TYPE_DATA_OBJECT)
            ->willReturn([
                ['cid' => 3, 'ctype' => 'object'],
                ['cid' => 7, 'ctype' => 'object'],
            ]);

        $query->expects($this->once())->method('searchByIds')->with([3, 7])->willReturnSelf();

        $result = $this->filter->apply($this->parametersWith('product_workflow', 'in_review'), $query);

        self::assertSame($query, $result);
    }

    public function testItSkipsOrphanedNonPositiveIds(): void
    {
        $query = $this->createMock(DataObjectQueryInterface::class);

        // Orphaned element_workflow_state rows can carry cid 0; the search index's IdsFilter
        // requires strictly positive ids, so they must be dropped before searchByIds.
        $this->repository->method('fetchByWorkflowState')->willReturn([
            ['cid' => 0, 'ctype' => 'object'],
            ['cid' => 7, 'ctype' => 'object'],
        ]);

        $query->expects($this->once())->method('searchByIds')->with([7])->willReturnSelf();

        $this->filter->apply($this->parametersWith('product_workflow', 'in_review'), $query);
    }

    public function testItUsesAssetElementTypeForAssetQueries(): void
    {
        $query = $this->createMock(AssetQueryInterface::class);

        $this->repository->expects($this->once())
            ->method('fetchByWorkflowState')
            ->with('asset_workflow', 'to_review', ElementTypes::TYPE_ASSET)
            ->willReturn([['cid' => 42, 'ctype' => 'asset']]);

        $query->expects($this->once())->method('searchByIds')->with([42])->willReturnSelf();

        $this->filter->apply($this->parametersWith('asset_workflow', 'to_review'), $query);
    }

    public function testItResolvesAllPlacesWhenNoPlaceIsGiven(): void
    {
        $query = $this->createMock(DataObjectQueryInterface::class);

        // No place -> null state name -> the whole workflow (the widget's show-all action).
        $this->repository->expects($this->once())
            ->method('fetchByWorkflowState')
            ->with('product_workflow', null, ElementTypes::TYPE_DATA_OBJECT)
            ->willReturn([['cid' => 5, 'ctype' => 'object']]);

        $query->expects($this->once())->method('searchByIds')->with([5])->willReturnSelf();

        $this->filter->apply($this->parametersWith('product_workflow', null), $query);
    }

    public function testItSearchesByAnEmptyIdSetWhenNoElementsMatch(): void
    {
        $query = $this->createMock(DataObjectQueryInterface::class);

        $this->repository->method('fetchByWorkflowState')->willReturn([]);
        // Empty id set -> the grid shows nothing (rather than everything).
        $query->expects($this->once())->method('searchByIds')->with([])->willReturnSelf();

        $this->filter->apply($this->parametersWith('product_workflow', 'in_review'), $query);
    }

    private function parametersWith(string $workflow, ?string $place): ColumnFiltersParameterInterface&MockObject
    {
        // ColumnFilter is a final readonly value object -> construct it, never mock it.
        $columnFilter = new ColumnFilter($workflow, WorkflowFilter::FILTER_TYPE, $place);

        $parameters = $this->createMock(ColumnFiltersParameterInterface::class);
        $parameters->method('getColumnFilterByType')
            ->with(WorkflowFilter::FILTER_TYPE)
            ->willReturn([$columnFilter]);

        return $parameters;
    }
}
