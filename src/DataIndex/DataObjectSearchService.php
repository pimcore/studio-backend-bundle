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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\DataObjectSearchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Element\Util\Trait\SearchTermTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;
use function count;

final readonly class DataObjectSearchService implements DataObjectSearchServiceInterface
{
    use SearchTermTrait;

    public function __construct(
        private DataObjectSearchAdapterInterface $dataObjectSearchAdapter,
        private DataObjectQueryProviderInterface $dataObjectQueryProvider
    ) {
    }

    public function searchDataObjects(QueryInterface $dataObjectQuery): DataObjectSearchResult
    {
        return $this->dataObjectSearchAdapter->searchDataObjects($dataObjectQuery);
    }

    public function getDataObjectById(int $id, ?UserInterface $user): DataObject|DataObjectFolder
    {
        return $this->dataObjectSearchAdapter->getDataObjectById($id, $user);
    }

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchDataObjectIds(QueryInterface $assetQuery): array
    {
        return $this->dataObjectSearchAdapter->fetchDataObjectIds($assetQuery);
    }

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function getChildrenIds(
        string $parentPath,
        ?string $sortDirection = null
    ): array {
        $query = $this->dataObjectQueryProvider->createDataObjectQuery();
        $query->filterPath($parentPath, true, false);
        if ($sortDirection) {
            $query->orderByPath($sortDirection);
        }

        return $this->dataObjectSearchAdapter->fetchDataObjectIds($query);
    }

    public function countChildren(
        string $parentPath,
        ?string $sortDirection = null
    ): int {

        return count($this->getChildrenIds($parentPath, $sortDirection));
    }

    /**
     * @throws NotFoundException|SearchException
     */
    public function getSearchTerm(string $searchTerm, ?UserInterface $user): int
    {
        $query = $this->dataObjectQueryProvider->createDataObjectQuery();
        $this->applySearchTerm($query, $searchTerm, $user);
        $result = $this->dataObjectSearchAdapter->fetchDataObjectIds($query);

        if (empty($result)) {
            throw new NotFoundException('asset', $searchTerm);
        }

        return reset($result);
    }

    /**
     * @throws InvalidSearchException|SearchException
     */
    public function findElementInTree(QueryInterface $query): ?ElementSearchResultItemInterface
    {
        return $this->dataObjectSearchAdapter->findInTree($query);
    }
}
