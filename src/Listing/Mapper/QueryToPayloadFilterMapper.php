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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Mapper;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;

final readonly class QueryToPayloadFilterMapper implements QueryToPayloadFilterMapperInterface
{
    public function map(mixed $parameters): FilterParameter
    {
        return $this->matchParameters($parameters);
    }

    private function matchParameters(mixed $parameters): FilterParameter
    {
        return match(true) {
            $parameters instanceof PropertiesParameters => $this->mapPropertiesParameters($parameters),
            default => throw new InvalidArgumentException('Invalid parameters type provided'),
        };
    }

    private function mapPropertiesParameters(PropertiesParameters $parameters): FilterParameter
    {
        $columnFilters = [];

        if ($parameters->getFilter() !== null) {
            $columnFilters[] = [
                'key' => 'properties',
                'type' => ColumnType::PROPERTY_NAME->value,
                'filterValue' => $parameters->getFilter(),
            ];
        }

        if ($parameters->getElementType() !== null) {
            $columnFilters[] = [
                'key' => 'properties',
                'type' => ColumnType::PROPERTY_ELEMENT_TYPE->value,
                'filterValue' => $parameters->getElementType(),
            ];
        }

        return new FilterParameter(
            columnFilters: $columnFilters,
        );
    }
}
