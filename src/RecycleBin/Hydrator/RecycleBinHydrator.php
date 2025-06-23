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
final readonly class RecycleBinHydrator implements RecycleBinHydratorInterface
{
    public function hydrate(Item $item): RecycleBin
    {
        return new RecycleBin(
            id: $item->getId(),
            amount: $item->getAmount(),
            date: $item->getDate(),
            deletedBy: $item->getDeletedby(),
            path: $item->getPath(),
            subtype: $item->getSubtype(),
            type: $item->getType()
        );
    }
}