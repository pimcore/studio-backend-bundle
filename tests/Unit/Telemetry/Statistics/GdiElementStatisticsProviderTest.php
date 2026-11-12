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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Telemetry\Statistics;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\DefaultSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\SearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\SearchResultAggregation;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\SearchResultAggregationBucket;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\SearchIndexServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Statistics\GdiElementStatisticsProvider;
use Pimcore\Telemetry\Snapshot\ElementTypeCounts;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;
use Pimcore\Telemetry\Snapshot\Statistics\TreeDepth;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * @internal
 */
final class GdiElementStatisticsProviderTest extends Unit
{
    public function testTypeCountsReadsTheTermsAggregation(): void
    {
        $result = $this->resultWith([
            new SearchResultAggregation('byType', [
                new SearchResultAggregationBucket('image', 10),
                new SearchResultAggregationBucket('folder', 2),
            ], 0, 0, []),
        ]);

        $counts = $this->provider($this->serviceReturning($result))->typeCounts(ElementKind::Asset);

        $this->assertSame(12, $counts->total());
        $this->assertSame(10, $counts->ofType('image'));
        $this->assertSame(2, $counts->distinctTypes());
    }

    /**
     * The index derives pathLevel differently for folders (getRealFullPath) than for other elements
     * (getPath), and variants have an empty path column, so no constant offset reproduces the
     * emitted slash-count. Depth must therefore come from SQL on every instance, healthy index or
     * not - otherwise two installs report different depths for identical data.
     */
    public function testTreeDepthAlwaysComesFromSqlEvenWhenTheIndexIsHealthy(): void
    {
        $healthyIndex = $this->serviceReturning($this->resultWith([
            new SearchResultAggregation('depthMax', [], 0, 0, ['value' => 3.0]),
            new SearchResultAggregation('depthAvg', [], 0, 0, ['value' => 2.0]),
        ]));

        $depth = $this->provider($healthyIndex, $this->innerReturningSentinels())
            ->treeDepth(ElementKind::DataObject);

        $this->assertSame(99, $depth->max, 'depth must be the inner SQL value, not pathLevel+1');
        $this->assertSame(88, $depth->avg);
    }

    public function testMaxObjectFanoutReadsTheTopParentBucket(): void
    {
        $result = $this->resultWith([
            new SearchResultAggregation('topParent', [new SearchResultAggregationBucket(5, 9)], 0, 0, []),
        ]);

        $this->assertSame(9, $this->provider($this->serviceReturning($result))->maxObjectFanout());
    }

    /**
     * A reachable but unpopulated index answers with an empty aggregation rather than an error. That
     * must not be read as "this instance has no assets" - the SQL truth wins.
     */
    public function testTypeCountsFallsBackToInnerOnAnEmptyIndex(): void
    {
        $emptyIndex = $this->serviceReturning($this->resultWith([
            new SearchResultAggregation('byType', [], 0, 0, []),
        ]));

        $counts = $this->provider($emptyIndex, $this->innerReturningSentinels())->typeCounts(ElementKind::Asset);

        $this->assertSame(7, $counts->ofType('sentinel'), 'an empty index must not shadow the SQL count');
    }

    public function testMaxObjectFanoutFallsBackToInnerOnAnEmptyIndex(): void
    {
        $emptyIndex = $this->serviceReturning($this->resultWith([
            new SearchResultAggregation('topParent', [], 0, 0, []),
        ]));

        $this->assertSame(
            333,
            $this->provider($emptyIndex, $this->innerReturningSentinels())->maxObjectFanout(),
            'an empty index must not report a fan-out of zero'
        );
    }

    public function testDegradesToInnerWhenTheIndexThrows(): void
    {
        $service = $this->createMock(SearchIndexServiceInterface::class);
        $service->method('createPaginatedSearch')->willThrowException(new RuntimeException('cluster down'));

        $provider = $this->provider($service, $this->innerReturningSentinels());

        $this->assertSame(7, $provider->typeCounts(ElementKind::Asset)->ofType('sentinel'));
        $this->assertSame(99, $provider->treeDepth(ElementKind::DataObject)->max);
        $this->assertSame(333, $provider->maxObjectFanout());
    }

    public function testVariantMetricsAlwaysDelegateToInner(): void
    {
        $provider = $this->provider(
            $this->createMock(SearchIndexServiceInterface::class),
            $this->innerReturningSentinels(),
        );

        $this->assertSame(111, $provider->objectsWithVariants());
        $this->assertSame(222, $provider->maxVariantsPerObject());
    }

    private function serviceReturning(SearchResult $result): SearchIndexServiceInterface
    {
        $search = $this->createMock(DefaultSearchInterface::class);
        $search->method('addAggregation')->willReturnSelf();

        $service = $this->createMock(SearchIndexServiceInterface::class);
        $service->method('createPaginatedSearch')->willReturn($search);
        $service->method('search')->willReturn($result);

        return $service;
    }

    /**
     * @param SearchResultAggregation[] $aggregations
     */
    private function resultWith(array $aggregations): SearchResult
    {
        return new SearchResult([], $aggregations, 0, null, $this->createMock(DefaultSearchInterface::class), []);
    }

    private function provider(
        SearchIndexServiceInterface $service,
        ?ElementStatisticsProviderInterface $inner = null,
    ): GdiElementStatisticsProvider {
        $config = $this->createMock(SearchIndexConfigServiceInterface::class);
        $config->method('getIndexName')->willReturn('idx');

        return new GdiElementStatisticsProvider(
            $inner ?? $this->createMock(ElementStatisticsProviderInterface::class),
            $service,
            $config,
            new NullLogger(),
        );
    }

    private function innerReturningSentinels(): ElementStatisticsProviderInterface
    {
        $inner = $this->createMock(ElementStatisticsProviderInterface::class);
        $inner->method('typeCounts')->willReturn(new ElementTypeCounts(['sentinel' => 7]));
        $inner->method('treeDepth')->willReturn(new TreeDepth(99, 88));
        $inner->method('objectsWithVariants')->willReturn(111);
        $inner->method('maxVariantsPerObject')->willReturn(222);
        $inner->method('maxObjectFanout')->willReturn(333);

        return $inner;
    }
}
