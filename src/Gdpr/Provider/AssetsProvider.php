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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy\AssetExporterInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\Response;
use function sprintf;

/**
 * @internal
 */
final readonly class AssetsProvider implements DataProviderInterface
{
    private array $assetConfig;

    public function __construct(
        private AssetQueryProviderInterface $query,
        private AssetSearchServiceInterface $searchService,
        private AssetExporterInterface $assetExporter,
        array $gdprConfig = []
    ) {
        $this->assetConfig = $gdprConfig['assets'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function findData(FilterParameter $filter): Collection
    {
        $query = $this->query->createAssetQuery();

        $query->excludeFolders();

        $idFilter = $filter->getSimpleColumnFilterByType('id');
        if ($idFilter !== null) {
            $query->filterInteger('id', (int)$idFilter->getFilterValue());
        }

        $textFilterTypes = ['firstname', 'lastname', 'email'];
        $searchTerms = [];

        foreach ($textFilterTypes as $filterType) {
            $textFilter = $filter->getSimpleColumnFilterByType($filterType);
            if ($textFilter === null) {
                continue;
            }

            $value = trim((string)$textFilter->getFilterValue());
            if ($value !== '') {
                $searchTerms[] = $value;
            }
        }

        if ($searchTerms !== []) {
            $query->filterMultiMatch(implode(' ', $searchTerms), [], 'cross_fields', 'and');
        }

        if ($this->assetConfig['types']) {
            $query->filterMultiSelect('type', $this->assetConfig['types']);
        }

        $this->applySearchOptions($query, $filter);

        $searchResult = $this->searchService->searchAssets($query);

        $items   = $searchResult->getItems();

        $rows = array_map(
            fn ($item) => new GdprDataRow([
                'type' => $item->getType(),
                'id' => $item->getId(),
                'fullPath' => $item->getFullPath(),
                'subType' => $item->getMimeType(),
                '__gdprIsDeletable' => true,
            ]),
            $items
        );

        return new Collection(
            totalItems: $searchResult->getTotalItems(),
            items: $rows
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

    /**
     * {@inheritdoc}
     */
    public function getSingleItemForDownload(int $id): Response
    {
        $asset = Asset::getById($id);

        if (!$asset) {
            throw new NotFoundException('Asset Not Found', $id);
        }

        if (!$asset->isAllowed(ElementPermissions::VIEW_PERMISSION)) {
            throw new ForbiddenException(sprintf('Access Denied for asset with id "%d".', $asset->getId()));
        }

        return $this->assetExporter->doExportData($asset);
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
}
