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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'UpdateUnitParameters',
    title: 'Update Unit Parameters',
    type: 'object'
)]
final readonly class UpdateUnitParameters implements UnitParametersInterface
{
    public function __construct(
        #[Property(description: 'Abbreviation', type: 'string', nullable: true, example: 'mm')]
        private ?string $abbreviation = null,
        #[Property(description: 'Long name', type: 'string', nullable: true, example: 'Millimeter')]
        private ?string $longname = null,
        #[Property(description: 'Group', type: 'string', nullable: true, example: 'Length')]
        private ?string $group = null,
        #[Property(description: 'Base unit ID', type: 'string', nullable: true, example: 'm')]
        private ?string $baseunit = null,
        #[Property(description: 'Conversion factor', type: 'number', nullable: true, example: 0.001)]
        private ?float $factor = null,
        #[Property(description: 'Conversion offset', type: 'number', nullable: true, example: null)]
        private ?float $conversionOffset = null,
        #[Property(description: 'Converter service class', type: 'string', nullable: true, example: null)]
        private ?string $converter = null,
        #[Property(description: 'Reference', type: 'string', nullable: true, example: null)]
        private ?string $reference = null,
    ) {
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function getLongname(): ?string
    {
        return $this->longname;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getBaseunit(): ?string
    {
        return $this->baseunit;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    public function getConversionOffset(): ?float
    {
        return $this->conversionOffset;
    }

    public function getConverter(): ?string
    {
        return $this->converter;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }
}
