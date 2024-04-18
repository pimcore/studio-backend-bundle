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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioApiBundle\Response\DataObject;
use Pimcore\Bundle\StudioApiBundle\Service\DataObjectSearchResult;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\DataObjectSearchAdapterInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

final readonly class DataObjectSearchAdapter implements DataObjectSearchAdapterInterface
{
    public function __construct(
        private DataObjectSearchServiceInterface $searchService,
        private ServiceResolver $serviceResolver
    ) {
    }

    public function searchDataObjects(DataObjectQuery $dataObjectQuery): DataObjectSearchResult
    {
        $searchResult = $this->searchService->search($dataObjectQuery->getSearch());
        $result = [];
        foreach($searchResult->getIds() as $id) {
            /** @var Concrete $dataObject */
            $dataObject = $this->getDataObjectById($id);
            $result[] = new DataObject($dataObject->getId(), $dataObject->getClassName());
        }

        return new DataObjectSearchResult(
            $result,
            $searchResult->getPagination()->getPage(),
            $searchResult->getPagination()->getPageSize(),
            $searchResult->getPagination()->getTotalItems(),
        );
    }

    public function getDataObjectById(int $id): ?ElementInterface
    {
        return $this->serviceResolver->getElementById('object', $id);
    }
}
