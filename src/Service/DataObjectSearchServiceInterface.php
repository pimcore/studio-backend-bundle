<?php

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

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Pimcore\Model\DataObject\Concrete;

interface DataObjectSearchServiceInterface
{
    public function searchDataObjects(DataObjectQuery $dataObjectQuery): DataObjectSearchResult;

    public function getDataObjectById(int $id): Concrete|null;
}
