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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;

/**
 * @internal
 */
trait NumberFilterTrait
{
    private function applyNumberFilter(ColumnFilter $column, QueryInterface $query, string $postFix = ''): QueryInterface
    {
        $filterValue = $column->getFilterValue();

        if (!isset($filterValue['setting'])) {
            throw new InvalidArgumentException('This filter requires a setting value');
        }
        $setting = $filterValue['setting'];

        if (isset($filterValue['is']) && $setting == 'is') {
            return $query->filterNumber($column->getKey().$postFix, $filterValue['is']);
        }

        if (isset($filterValue['to']) && $setting == 'less') {
            return $query->filterNumberRange($column->getKey().$postFix, null, $filterValue['to']);
        }

        if (isset($filterValue['from']) && $setting == 'more') {
            return $query->filterNumberRange($column->getKey().$postFix, $filterValue['from'], null);
        }

        if ($setting == 'between') {
            return $query->filterNumberRange($column->getKey().$postFix, $filterValue['from'], $filterValue['to']);
        }

        throw new InvalidArgumentException('Unable to apply number filter no correct setting given');
    }
}
