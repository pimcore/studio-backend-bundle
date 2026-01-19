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
     * {@inheritdoc}
     */
    public function getAssetSearchGrid(SearchGridParameter $gridParameter): Collection
    {
        $filter = $gridParameter->getFilters();
        $filter->setExcludeFolders(false);
        $parameter = new GridParameter(
            folderId: 1,
            columns: $gridParameter->getColumns(),
            filters: $filter
        );

        return $this->gridService->getAssetGrid($parameter);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataObjectSearchGrid(SearchGridParameter $searchParameter, ?string $classId): Collection
    {
        $filter = $searchParameter->getFilters();
        $filter->setExcludeFolders(false);

        $gridParameter = new GridParameter(
            folderId: 1,
            columns: $searchParameter->getColumns(),
            filters: $filter
        );

        return $this->gridService->getDataObjectGrid($gridParameter, $classId);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentSearchGrid(SearchGridParameter $searchParameter): Collection
    {
        $filter = $searchParameter->getFilters();
        $filter->setExcludeFolders(false);

        $gridParameter = new GridParameter(
            folderId: 1,
            columns: $searchParameter->getColumns(),
            filters: $filter
        );

        return $this->gridService->getDocumentGrid($gridParameter);
    }
}
