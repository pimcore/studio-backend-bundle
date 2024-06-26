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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;

interface DataObjectSearchServiceInterface
{
    public function searchDataObjects(QueryInterface $dataObjectQuery): DataObjectSearchResult;

    public function getDataObjectById(int $id): DataObject;

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
}
