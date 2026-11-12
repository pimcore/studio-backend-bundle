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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry\Statistics;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\FieldCategory\SystemField;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\IndexName;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Aggregation\Aggregation;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\SearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\SearchIndexServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Telemetry\Snapshot\ElementTypeCounts;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;
use Pimcore\Telemetry\Snapshot\Statistics\TreeDepth;
use Psr\Log\LoggerInterface;

/**
 * Search-index-backed {@see ElementStatisticsProviderInterface} that decorates the core SQL provider.
 *
 * Two of the snapshot's data-volume-scaling statistics are the exact shape the Generic Data Index
 * answers cheaply, and those are the ones served here: counts-by-type as a `terms` aggregation and
 * folder fan-out as `terms(parentId, size:1, desc)` - instance-wide OpenSearch/Elasticsearch
 * aggregations that never touch the transactional database and never scan.
 *
 * It is instance-wide by design, so it uses the low-level {@see SearchIndexServiceInterface} (not the
 * permission/workspace-scoped element search services). Each accelerated method degrades to the
 * decorated SQL provider ($inner) on an `Exception` - an unreachable cluster, a rejected query, an
 * empty index. Errors are deliberately not caught: a defect here should surface at the snapshot
 * boundary rather than masquerade as a healthy SQL-derived number - so telemetry
 * keeps working on instances without a healthy search cluster, and an index that lags the DB only
 * affects the always-bucketed values within one bucket.
 *
 * The remaining methods delegate to SQL unconditionally, so that every instance emits the same
 * number for the same data regardless of index health:
 *  - Tree depth: the index cannot reproduce the emitted slash-count semantics - see
 *    {@see self::treeDepth()}.
 *  - The variant metrics need a `type = variant` filter and hit the indexed `objects.parentId`
 *    cheaply in SQL anyway.
 *
 * Coverage note for what IS served from the index: a few element subtypes are not indexed (e.g.
 * web-to-print documents); the values this collector emits are either bucketed or an exactly-indexed
 * subtype, so emitted telemetry matches.
 *
 * @internal
 */
final readonly class GdiElementStatisticsProvider implements ElementStatisticsProviderInterface
{
    private const TERMS_SIZE = 1000;

    public function __construct(
        private ElementStatisticsProviderInterface $inner,
        private SearchIndexServiceInterface $searchIndexService,
        private SearchIndexConfigServiceInterface $indexConfig,
        private LoggerInterface $logger,
    ) {
    }

    public function typeCounts(ElementKind $kind): ElementTypeCounts
    {
        try {
            $result = $this->aggregate($kind, [
                new Aggregation(
                    'byType',
                    ['terms' => ['field' => SystemField::TYPE->getPath(), 'size' => self::TERMS_SIZE]]
                ),
            ]);

            $byType = [];
            foreach ($result->getAggregation('byType')?->getBuckets() ?? [] as $bucket) {
                $byType[(string) $bucket->getKey()] = $bucket->getDocCount();
            }

            // An empty result means the index isn't populated for this kind - prefer the SQL truth.
            return $byType === [] ? $this->inner->typeCounts($kind) : new ElementTypeCounts($byType);
        } catch (Exception $e) {
            $this->logFallback('typeCounts', $e);

            return $this->inner->typeCounts($kind);
        }
    }

    /**
     * Delegated to SQL on purpose - the index cannot reproduce the emitted metric.
     *
     * The emitted depth is the slash count of an element's `path` column. `system_fields.pathLevel`
     * is not a constant offset from that, because the index derives it differently per element:
     * `getRealFullPath()` for folders (which includes the element's own key) but `getPath()` for
     * everything else, so folders are already one segment deeper. Variants diverge again - their
     * `path` column is empty, so SQL scores them 0 while the index gives them a real level. A single
     * `+1` correction is therefore wrong for two element classes, and an instance with a healthy
     * index would report different depths than one without - verified against live data.
     *
     * Reproducing the SQL semantics here would need per-type filtered aggregations; until that is
     * worth doing, correctness and cross-instance comparability win over the saved scan. The
     * inner provider is time-boxed, so the cost is bounded.
     */
    public function treeDepth(ElementKind $kind): TreeDepth
    {
        return $this->inner->treeDepth($kind);
    }

    public function objectsWithVariants(): int
    {
        return $this->inner->objectsWithVariants();
    }

    public function maxVariantsPerObject(): int
    {
        return $this->inner->maxVariantsPerObject();
    }

    public function maxObjectFanout(): int
    {
        try {
            $result = $this->aggregate(ElementKind::DataObject, [
                new Aggregation('topParent', ['terms' => [
                    'field' => SystemField::PARENT_ID->getPath(),
                    'size' => 1,
                    'order' => ['_count' => 'desc'],
                ]]),
            ]);

            $buckets = $result->getAggregation('topParent')?->getBuckets() ?? [];

            return $buckets === [] ? $this->inner->maxObjectFanout() : $buckets[0]->getDocCount();
        } catch (Exception $e) {
            $this->logFallback('maxObjectFanout', $e);

            return $this->inner->maxObjectFanout();
        }
    }

    /**
     * @param Aggregation[] $aggregations
     */
    private function aggregate(ElementKind $kind, array $aggregations): SearchResult
    {
        $search = $this->searchIndexService->createPaginatedSearch(1, 0, true);
        foreach ($aggregations as $aggregation) {
            $search->addAggregation($aggregation);
        }

        return $this->searchIndexService->search($search, $this->indexName($kind));
    }

    private function indexName(ElementKind $kind): string
    {
        return $this->indexConfig->getIndexName(match ($kind) {
            ElementKind::Asset => IndexName::ASSET->value,
            ElementKind::Document => IndexName::DOCUMENT->value,
            ElementKind::DataObject => IndexName::DATA_OBJECT->value,
        });
    }

    private function logFallback(string $operation, Exception $e): void
    {
        $this->logger->info('Telemetry: GDI statistics unavailable for {op}, falling back to SQL: {msg}', [
            'op' => $operation,
            'msg' => $e->getMessage(),
        ]);
    }
}
