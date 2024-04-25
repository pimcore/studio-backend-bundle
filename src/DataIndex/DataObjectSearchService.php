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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\DataObjectSearchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Model\DataObject\Concrete;

final readonly class DataObjectSearchService implements DataObjectSearchServiceInterface
{
    public function __construct(private DataObjectSearchAdapterInterface $dataObjectSearchAdapter)
    {
    }

    public function searchDataObjects(QueryInterface $dataObjectQuery): DataObjectSearchResult
    {
        return $this->dataObjectSearchAdapter->searchDataObjects($dataObjectQuery);
    }

    public function getDataObjectById(int $id): Concrete|null
    {
        return null;
    }
}
