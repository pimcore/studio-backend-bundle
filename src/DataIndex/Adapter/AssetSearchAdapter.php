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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\AssetSearchException;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\OpenSearch\SearchFailedException;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\AssetHydratorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SearchException;

final readonly class AssetSearchAdapter implements AssetSearchAdapterInterface
{
    public function __construct(
        private AssetSearchServiceInterface $searchService,
        private AssetHydratorServiceInterface $assetHydratorService
    ) {
    }

    /**
     * @throws SearchException
     */
    public function searchAssets(QueryInterface $assetQuery): AssetSearchResult
    {
        try {
            $searchResult = $this->searchService->search($assetQuery->getSearch());
        } catch (AssetSearchException) {
            throw new SearchException('assets');
        }

        $result = [];
        foreach ($searchResult->getItems() as $item) {
            $result[] = $this->assetHydratorService->hydrate($item);
        }

        return new AssetSearchResult(
            $result,
            $searchResult->getPagination()->getPage(),
            $searchResult->getPagination()->getPageSize(),
            $searchResult->getPagination()->getTotalItems(),
        );
    }

    /**
     * @throws SearchFailedException|ElementNotFoundException
     */
    public function getAssetById(int $id): Asset
    {
        try {
            $asset = $this->searchService->byId($id);
        } catch (AssetSearchException) {
            throw new SearchException(sprintf('asset with id %s', $id));
        }

        if (!$asset) {
            throw new ElementNotFoundException($id);
        }

        return $this->assetHydratorService->hydrate($asset);
    }
}
