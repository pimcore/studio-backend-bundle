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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;

interface DataObjectSearchServiceInterface
{
    public function searchDataObjects(QueryInterface $dataObjectQuery): DataObjectSearchResult;

    public function getDataObjectById(int $id, ?UserInterface $user): DataObject|DataObjectFolder;

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchDataObjectIds(QueryInterface $assetQuery): array;

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function getChildrenIds(
        string $parentPath,
        ?string $sortDirection = null
    ): array;

    /**
     * @throws SearchException
     *
     */
    public function countChildren(
        string $parentPath,
        ?string $sortDirection = null
    ): int;

    /**
     * @throws NotFoundException|SearchException
     */
    public function getSearchTerm(string $searchTerm, ?UserInterface $user): int;

    /**
     * @throws InvalidSearchException|SearchException
     */
    public function findElementInTree(QueryInterface $query): ?ElementSearchResultItemInterface;
}
