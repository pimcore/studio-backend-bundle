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
