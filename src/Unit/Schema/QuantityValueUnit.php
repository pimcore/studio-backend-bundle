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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'QuantityValueUnit',
    required: [
        'id',
        'abbreviation',
        'group',
        'longName',
        'baseUnit',
        'reference',
        'factor',
        'conversionOffset',
        'converter',
    ],
    type: 'object'
)]
class QuantityValueUnit implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'string', example: 'mm')]
        private readonly ?string $id,
        #[Property(description: 'Abbreviation', type: 'string', example: 'mm')]
        private readonly ?string $abbreviation,
        #[Property(description: 'Group', type: 'string', example: null)]
        private readonly ?string $group,
        #[Property(description: 'Long Name', type: 'string', example: 'Millimeter')]
        private readonly ?string $longName,
        #[Property(description: 'Base Unit', type: 'string', example: 'm')]
        private readonly ?string $baseUnit,
        #[Property(description: 'Reference', type: 'string', example: null)]
        private readonly ?string $reference,
        #[Property(description: 'Factor', type: 'float', example: null)]
        private readonly ?float $factor,
        #[Property(description: 'Conversion Offset', type: 'float', example: null)]
        private readonly ?float $conversionOffset,
        #[Property(description: 'Converter', type: 'string', example: null)]
        private readonly ?string $converter,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getLongName(): ?string
    {
        return $this->longName;
    }

    public function getBaseUnit(): ?string
    {
        return $this->baseUnit;
    }

    public function getReference(): ?string
    {
        return $this->reference;
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
}
