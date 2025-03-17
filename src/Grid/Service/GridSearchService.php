<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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
    )
    {
    }

    /**
     * {@inheritDoc}
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
}