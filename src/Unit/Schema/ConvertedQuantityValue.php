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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Converted quantity values',
    required: [
        'originalValue',
        'fromUnitId',
        'convertedValues',
    ],
    type: 'object'
)]
final readonly class ConvertedQuantityValue
{
    public function __construct(
        #[Property(description: 'Unit Abbreviation', type: 'string', example: 'm')]
        private string $unitAbbreviation,
        #[Property(description: 'Unit Long Name', type: 'string', example: 'Meter')]
        private string $unitLongName,
        #[Property(description: 'Converted Values', type: 'float', example: 160)]
        private float $convertedValue,
    ) {
    }

    public function getUnitAbbreviation(): string
    {
        return $this->unitAbbreviation;
    }

    public function getUnitLongName(): string
    {
        return $this->unitLongName;
    }

    public function getConvertedValue(): float
    {
        return $this->convertedValue;
    }
}
