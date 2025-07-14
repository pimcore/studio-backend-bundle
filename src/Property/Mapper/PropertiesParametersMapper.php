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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Mapper\FilterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;

final class PropertiesParametersMapper implements FilterMapperInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function map(mixed $parameters): FilterParameter
    {
        if (!$parameters instanceof PropertiesParameters) {
            throw new InvalidArgumentException('Invalid parameters type provided');
        }

        $columnFilters = [];

        if ($parameters->getFilter() !== null) {
            $columnFilters[] = [
                'key' => 'properties',
                'type' => FilterType::PROPERTY_FILTER->value,
                'filterValue' => $parameters->getFilter(),
            ];
        }

        if ($parameters->getElementType() !== null) {
            $columnFilters[] = [
                'key' => 'properties',
                'type' => FilterType::PROPERTY_ELEMENT_TYPE->value,
                'filterValue' => $parameters->getElementType(),
            ];
        }

        return new FilterParameter(
            columnFilters: $columnFilters,
        );
    }
}
