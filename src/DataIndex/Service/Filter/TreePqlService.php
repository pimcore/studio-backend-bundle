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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Filter;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Aggregation\Tree\ChildFolderAggregation;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\ExcludeFoldersFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\QueryLanguage\PqlFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\SearchResultAggregation;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Document\DocumentSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final readonly class TreePqlService implements TreePqlServiceInterface
{
    public function __construct(
        private SearchProviderInterface $searchProvider,
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DocumentSearchServiceInterface $documentSearchService,
        private LoggerInterface $logger,
    ) {
    }

    public function getRelevantChildFolderKeys(
        int $parentId,
        string $pqlQuery,
        string $elementType,
    ): array {
        $parentPath = $this->resolveParentPath($parentId, $elementType);
        if ($parentPath === null) {
            return [];
        }

        $aggregation = $this->executeAggregationSearch($parentPath, $pqlQuery, $elementType);
        if ($aggregation === null) {
            return [];
        }

        return $this->extractFolderKeysFromAggregation($aggregation->getAggregationResult());
    }

    private function resolveParentPath(int $parentId, string $elementType): ?string
    {
        if ($parentId === 1) {
            return '/';
        }

        $element = match ($elementType) {
            ElementTypes::TYPE_ASSET => $this->assetSearchService->byId($parentId),
            ElementTypes::TYPE_DATA_OBJECT,
            ElementTypes::TYPE_OBJECT => $this->dataObjectSearchService->byId($parentId),
            ElementTypes::TYPE_DOCUMENT => $this->documentSearchService->byId($parentId),
            default => null,
        };

        return $element?->getFullPath();
    }

    private function executeAggregationSearch(
        string $parentPath,
        string $pqlQuery,
        string $elementType,
    ): ?SearchResultAggregation {
        $aggregationName = ChildFolderAggregation::AGGREGATION_NAME;

        try {
            return match ($elementType) {
                ElementTypes::TYPE_ASSET => $this->assetSearchService
                    ->search($this->prepareSearch(
                        $this->searchProvider->createAssetSearch(),
                        $parentPath,
                        $pqlQuery,
                    ))
                    ->getAggregation($aggregationName),
                ElementTypes::TYPE_DATA_OBJECT,
                ElementTypes::TYPE_OBJECT => $this->dataObjectSearchService
                    ->search($this->prepareSearch(
                        $this->searchProvider->createDataObjectSearch(),
                        $parentPath,
                        $pqlQuery,
                    ))
                    ->getAggregation($aggregationName),
                ElementTypes::TYPE_DOCUMENT => $this->documentSearchService
                    ->search($this->prepareSearch(
                        $this->searchProvider->createDocumentSearch(),
                        $parentPath,
                        $pqlQuery,
                    ))
                    ->getAggregation($aggregationName),
                default => null,
            };
        } catch (Exception $e) {
            $this->logger->warning(
                'Tree PQL folder aggregation query failed, falling back to non-folder results only',
                [
                    'parentPath' => $parentPath,
                    'pqlQuery' => $pqlQuery,
                    'elementType' => $elementType,
                    'exception' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    /**
     * @template T of SearchInterface
     *
     * @param T $search
     *
     * @return T
     */
    private function prepareSearch(
        SearchInterface $search,
        string $parentPath,
        string $pqlQuery,
    ): SearchInterface {
        $search->addModifier(new PathFilter($parentPath));
        $search->addModifier(new ExcludeFoldersFilter());
        $search->addModifier(new PqlFilter($pqlQuery));
        $search->addModifier(new ChildFolderAggregation($parentPath));
        $search->setAggregationsOnly(true);

        return $search;
    }

    private function extractFolderKeysFromAggregation(array $aggregationResult): array
    {
        $buckets = $aggregationResult['filtered_level']['folder_names']['buckets'] ?? [];

        return array_map(
            static fn (array $bucket): string => (string) $bucket['key'],
            $buckets,
        );
    }
}
