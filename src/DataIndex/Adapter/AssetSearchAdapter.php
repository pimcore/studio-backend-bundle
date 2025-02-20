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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\Aggregation\FileSizeAggregationServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchResultIdListServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\HydratorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function get_class;
use function sprintf;

final readonly class AssetSearchAdapter implements AssetSearchAdapterInterface
{
    public function __construct(
        private AssetSearchServiceInterface $searchService,
        private HydratorServiceInterface $hydratorService,
        private SearchResultIdListServiceInterface $searchResultIdListService,
        private FileSizeAggregationServiceInterface $fileSizeAggregationService,
    ) {
    }

    /**
     * @throws SearchException|InvalidArgumentException
     */
    public function searchAssets(AssetQueryInterface $assetQuery): AssetSearchResult
    {
        try {
            $searchResult = $this->searchService->search($assetQuery->getSearch());
        } catch (AssetSearchException) {
            throw new SearchException('assets');
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $result = [];
        foreach ($searchResult->getItems() as $item) {
            $result[] = $this->hydratorService->hydrateAssets($item);
        }

        return new AssetSearchResult(
            $result,
            $searchResult->getPagination()->getPage(),
            $searchResult->getPagination()->getPageSize(),
            $searchResult->getPagination()->getTotalItems(),
        );
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetById(int $id, ?UserInterface $user = null): Asset
    {

        try {
            /** @var User $user
             *  Because of byId method in the GDI
             * */
            $asset = $this->searchService->byId($id, $user);
        } catch (AssetSearchException) {
            throw new SearchException(sprintf('Asset with id %s', $id));
        }

        if (!$asset) {
            throw new NotFoundException('Asset', $id);
        }

        return $this->hydratorService->hydrateAssets($asset);
    }

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchAssetIds(QueryInterface $assetQuery): array
    {
        try {
            $search = $assetQuery->getSearch();
            $search->addModifier(new OrderByFullPath());

            return $this->searchResultIdListService->getAllIds($search);
        } catch (AssetSearchException) {
            throw new SearchException('assets');
        }
    }

    /**
     * @throws InvalidSearchException
     */
    public function getTotalFileSizeByIds(QueryInterface $assetQuery): int
    {
        $search = $assetQuery->getSearch();
        if (!$search instanceof AssetSearch) {
            throw new AssetSearchException('Invalid search query');
        }

        return $this->fileSizeAggregationService->getFileSizeSum($search);
    }

    /**
     * @throws InvalidSearchException|SearchException
     */
    public function findInTree(QueryInterface $dataObjectQuery): ?ElementSearchResultItemInterface
    {
        try {
            $searchResult = $this->searchService->search(
                $this->validateSearch($dataObjectQuery->getSearch())
            );
        } catch (AssetSearchException) {
            throw new SearchException('Asset');
        }

        $results = $searchResult->getItems();
        if (empty($results)) {
            return null;
        }

        return reset($results);
    }

    /**
     * @throws InvalidSearchException
     */
    private function validateSearch(SearchInterface $search): AssetSearchInterface
    {
        if (!$search instanceof AssetSearchInterface) {
            throw new InvalidSearchException(
                HttpResponseCodes::BAD_REQUEST->value,
                sprintf(
                    'Expected search to be an instance of %s, got %s',
                    AssetSearchInterface::class,
                    get_class($search)
                )
            );
        }

        return $search;
    }
}
