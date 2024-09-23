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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
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
    public function __construct(
        private FilterServiceProviderInterface $filterServiceProvider,
        private AssetSearchServiceInterface $assetSearchService,
        private AssetServiceInterface $assetService
    ) {
    }

    /**
     * @throws NotFoundException|SearchException|InvalidArgumentException
     */
    public function searchAssets(GridParameter $gridParameter): AssetSearchResult
    {
        /** @var OpenSearchFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);
        $filter = $gridParameter->getFilters();

        $asset = $this->assetService->getAssetFolder($gridParameter->getFolderId());

        $filter->setPath($asset->getFullPath());

        $assetQuery = $filterService->applyFilters(
            $filter,
            ElementTypes::TYPE_ASSET
        );

        return $this->assetSearchService->searchAssets($assetQuery);
    }
}
