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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;

/**
 * @internal
 */
trait IsAssetFilterTrait
{
    public function validateParameterType(mixed $parameters): ?ColumnFiltersParameterInterface
    {
        if ($parameters instanceof ColumnFiltersParameterInterface) {
            return $parameters;
        }

        return null;
    }

    public function validateQueryType(mixed $query): ?AssetQueryInterface
    {
        if ($query instanceof AssetQueryInterface) {
            return $query;
        }

        return null;
    }
}
