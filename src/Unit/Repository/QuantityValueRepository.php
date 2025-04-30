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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Repository;

use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\Unit\Listing;

/**
 * @internal
 */
final readonly class QuantityValueRepository implements QuantityValueRepositoryInterface
{
    /**
     * @return Unit[]
     */
    public function getUnitList(): array
    {
        $list = new Listing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);

        return $list->getUnits();
    }

    /**
     * @return Unit[]
     */
    public function getUnitListByBaseUnit(string $baseUnitId, string $fromUnitId): array
    {
        $list = new Listing();
        $list->setCondition(
            'baseunit = ' . $list->quote($baseUnitId) .
            ' AND id != ' . $list->quote($fromUnitId)
        );

        return $list->getUnits();
    }
}
