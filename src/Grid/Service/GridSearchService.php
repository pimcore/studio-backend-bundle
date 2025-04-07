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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\SearchGridParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
final readonly class GridSearchService implements GridSearchServiceInterface
{
    public function __construct(
        private GridServiceInterface $gridService
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getAssetSearchGrid(
        SearchGridParameter $gridParameter,
        array $columnDefinitions
    ): Collection {
        $filter = $gridParameter->getFilters();
        $filter->setExcludeFolders(false);
        $parameter = new GridParameter(
            folderId: 1,
            columns: $gridParameter->getColumns(),
            filters: $filter
        );

        return $this->gridService->getAssetGrid($parameter, $columnDefinitions);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataObjectSearchGrid(
        SearchGridParameter $searchParameter,
        ?string $classId
    ): Collection {
        $filter = $searchParameter->getFilters();
        $filter->setExcludeFolders(false);

        $gridParameter = new GridParameter(
            folderId: 1,
            columns: $searchParameter->getColumns(),
            filters: $filter
        );

        return $this->gridService->getDataObjectGrid($gridParameter, $classId);
    }
}
