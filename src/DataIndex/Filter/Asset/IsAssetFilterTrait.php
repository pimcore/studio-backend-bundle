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
