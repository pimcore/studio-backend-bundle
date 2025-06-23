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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RecycleBin;
use Pimcore\Model\Element\Recyclebin\Item;

/**
 * @internal
 */
interface RecycleBinHydratorInterface
{
    public function hydrate(Item $item): RecycleBin;
}
