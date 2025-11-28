<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy\AssetExporterInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataColumn;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\Asset;

/**
 * @internal
 */
final readonly class AssetsProvider implements DataProviderInterface
{
    public function __construct(
        private AssetQueryProviderInterface $query,
        private AssetSearchServiceInterface $searchService,
        private AssetExporterInterface $assetExporter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function findData(SearchTerms $terms, FilterParameter $options): array
    {
        $query = $this->query->createAssetQuery();

        $query->excludeFolders();

        if ($terms->getId() !== null) {
            $query->filterInteger('id', $terms->getId());
        }

        $texts = [
            $terms->getFirstname(),
            $terms->getLastname(),
            $terms->getEmail(),
        ];

        foreach ($texts as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $query->filterFullText($value);
            }
        }

        $this->applySearchOptions($query, $options);

        $searchResult = $this->searchService->searchAssets($query);

        $columns = $this->getAvailableColumns();

        $items   = $searchResult->getItems();

        return array_map(
            fn ($item) => new GdprDataRow([
                'type' => $item->getType(),
                'id' => $item->getId(),
                'fullPath' => $item->getFullPath(),
                'subType' => $item->getMimeType(),
            ], $columns),
            $items
        );
    }

    private function applySearchOptions(QueryInterface $query, FilterParameter $options): void
    {
        $query->setPage($options->getPage());
        $query->setPageSize($options->getPageSize());

        $sortFilter = $options->getSortFilter();

        if ($sortFilter->getKey() && $sortFilter->getDirection()) {
            $directionEnum = strtolower($sortFilter->getDirection()) === SortDirection::DESC->value
                ? SortDirection::DESC
                : SortDirection::ASC;

            $query->orderByField($sortFilter->getKey(), $directionEnum);
        }

    }

    public function getDeleteSwaggerOperationId(): string
    {
        return 'pimcore_studio_api_assets_batch_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleItemForDownload(int $id): array
    {
        try {
            $asset = Asset::getById((int)$id);

        } catch (NotFoundException) {
            throw new NotFoundException('Asset Not Found', $id);
        }

        return $this->assetExporter->doexportAsset($asset);

    }

    public function getName(): string
    {
        return 'Assets';
    }

    public function getKey(): string
    {
        return 'assets';
    }

    public function getSortPriority(): int
    {
        return 8;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPermissions(): array
    {
        return [UserPermissions::ASSETS->value];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableColumns(): array
    {
        return [
            new GdprDataColumn('type', 'Type'),
            new GdprDataColumn('id', 'ID'),
            new GdprDataColumn('fullPath', 'Full Path'),
            new GdprDataColumn('subType', 'Type'),
        ];
    }
}
