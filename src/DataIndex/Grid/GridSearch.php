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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final readonly class GridSearch implements GridSearchInterface
{
    private OpenSearchFilterInterface $filterService;

    public function __construct(
        private FilterServiceProviderInterface $filterServiceProvider,
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private AssetServiceInterface $assetService,
        private DataObjectServiceInterface $dataObjectService
    ) {
        $this->filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);
    }

    /**
     * @throws NotFoundException|SearchException|InvalidArgumentException
     */
    public function searchAssets(GridParameter $gridParameter): AssetSearchResult
    {
        $filter = $gridParameter->getFilters();

        $asset = $this->assetService->getAssetFolder($gridParameter->getFolderId());

        $filter->setPath($asset->getFullPath());

        $assetQuery = $this->filterService->applyFilters(
            $filter,
            ElementTypes::TYPE_ASSET
        );

        // TODO remove assetSearchService, replace with AssetService @martineiber
        return $this->assetSearchService->searchAssets($assetQuery);
    }

    public function searchDataObjects(GridParameter $gridParameter): DataObjectSearchResult
    {
        $filter = $gridParameter->getFilters();

        $folder = $this->dataObjectService->getDataObjectFolder($gridParameter->getFolderId());

        $filter->setPath($folder->getFullPath());

        $query = $this->filterService->applyFilters(
            $filter,
            ElementTypes::TYPE_DATA_OBJECT
        );

        // TODO remove dataObjectSearchService, replace with DataObjectService @martineiber
        return $this->dataObjectSearchService->searchDataObjects($query);
    }
}
