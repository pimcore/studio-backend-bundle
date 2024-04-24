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

namespace Pimcore\Bundle\StudioApiBundle\DataIndex\Adapter;

use Pimcore\Bundle\StudioApiBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioApiBundle\DataIndex\Query\QueryInterface;
use Pimcore\Model\Element\ElementInterface;

interface DataObjectSearchAdapterInterface
{
    public function searchDataObjects(QueryInterface $dataObjectQuery): DataObjectSearchResult;

    public function getDataObjectById(int $id): ?ElementInterface;
}
