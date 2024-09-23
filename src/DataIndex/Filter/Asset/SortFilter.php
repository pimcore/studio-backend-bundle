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

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;

/**
 * @internal
 */
final class SortFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$query instanceof AssetQueryInterface) {
            return $query;
        }

        if (!$parameters instanceof SortFilterParameterInterface) {
            return $query;
        }

        $sortFilter = $parameters->getSortFilter();

        $sortDirection = SortDirection::ASC;

        if (strtolower($sortFilter->getDirection()) === SortDirection::DESC->value) {
            $sortDirection = SortDirection::DESC;
        }

        $query->orderByField(
            $sortFilter->getKey(),
            $sortDirection
        );

        return $query;
    }
}
