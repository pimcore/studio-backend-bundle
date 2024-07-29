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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\OpenSearchFieldMappingInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;

/**
 * @internal
 */
final class SortFilter implements FilterInterface
{
    public function __construct(
        private readonly OpenSearchFieldMappingInterface $openSearchFieldMapping
    ) {
    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$query instanceof AssetQuery) {
            return $query;
        }

        if (!$parameters instanceof SortFilterParameterInterface) {
            return $query;
        }

        $sortFilter = $parameters->getSortFilter();

        if (!$sortFilter) {
            return $query;
        }

        $sortDirection = SortDirection::ASC;

        if (strtolower($sortFilter->getDirection()) === SortDirection::DESC->value) {
            $sortDirection = SortDirection::DESC;
        }

        $query->orderByField(
            $this->openSearchFieldMapping->getOpenSearchKey($sortFilter->getKey()),
            $sortDirection
        );

        return $query;
    }
}
