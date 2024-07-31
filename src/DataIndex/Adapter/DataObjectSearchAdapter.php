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

use Pimcore\Bundle\GenericDataIndexBundle\Exception\DataObjectSearchException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchResultIdListServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\Folder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use function get_class;
use function sprintf;

final readonly class DataObjectSearchAdapter implements DataObjectSearchAdapterInterface
{
    public function __construct(
        private DataObjectSearchServiceInterface $searchService,
        private SearchResultIdListServiceInterface $searchResultIdListService,
    ) {
    }

    /**
     * @throws InvalidSearchException
     */
    public function searchDataObjects(QueryInterface $dataObjectQuery): DataObjectSearchResult
    {

        $search = $dataObjectQuery->getSearch();
        if (!$search instanceof DataObjectSearchInterface) {
            throw new InvalidSearchException(
                HttpResponseCodes::BAD_REQUEST->value,
                sprintf(
                    'Expected search to be an instance of %s, got %s',
                    DataObjectSearchInterface::class,
                    get_class($search)
                )
            );
        }
        $searchResult = $this->searchService->search($search);

        $result = array_map(static function (DataObjectSearchResultItem $item) {
            return new DataObject($item->getId(), $item->getClassName());
        }, $searchResult->getItems());

        return new DataObjectSearchResult(
            $result,
            $searchResult->getPagination()->getPage(),
            $searchResult->getPagination()->getPageSize(),
            $searchResult->getPagination()->getTotalItems(),
        );
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObjectById(int $id): DataObject
    {
        try {
            $dataObject = $this->searchService->byId($id);
        } catch (DataObjectSearchException) {
            throw new SearchException(sprintf('DataObject with id %s', $id));
        }

        if (!$dataObject) {
            throw new NotFoundException('DataObject', $id);
        }

        return new DataObject($dataObject->getId(), $dataObject->getClassName());
    }

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchAssetIds(QueryInterface $dataObjectQuery): array
    {
        try {
            $search = $dataObjectQuery->getSearch();
            $search->addModifier(new OrderByFullPath());

            return $this->searchResultIdListService->getAllIds($search);
        } catch (DataObjectSearchException) {
            throw new SearchException('dataObjects');
        }
    }
}
