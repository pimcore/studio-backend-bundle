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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Mapper\FilterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;

final class CollectionParametersMapper implements FilterMapperInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function map(mixed $parameters): FilterParameter
    {
        if (!$parameters instanceof CollectionParameters) {
            return new FilterParameter();
        }

        $columnFilters = [];

        if ($parameters->getPage() !== null) {
            $columnFilters[] = [
                'key' => 'page',
                'type' => FilterType::PAGE->value,
                'filterValue' => $parameters->getPage(),
            ];
        }

        if ($parameters->getPageSize() !== null) {
            $columnFilters[] = [
                'key' => 'pageSize',
                'type' => FilterType::PAGE_SIZE->value,
                'filterValue' => $parameters->getPageSize(),
            ];
        }

        return new FilterParameter(
            columnFilters: $columnFilters,
        );
    }
}
