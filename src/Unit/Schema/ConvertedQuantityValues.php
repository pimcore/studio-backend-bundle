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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Converted quantity values',
    required: [
        'originalValue',
        'fromUnitId',
        'convertedValues',
    ],
    type: 'object'
)]
class ConvertedQuantityValues implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'Original Value',
            example: 5,
            anyOf: [new Schema(type: 'float'), new Schema(type: 'integer')]
        )]
        private readonly float|int $originalValue,
        #[Property(description: 'From Unit Id', type: 'string', example: 'm')]
        private readonly string $fromUnitId,
        #[Property(
            description: 'Converted Values',
            type: 'array',
            items: new Items(ConvertedQuantityValue::class)
        )]
        private readonly array $convertedValues,
    ) {
    }

    public function getOriginalValue(): float|int
    {
        return $this->originalValue;
    }

    public function getFromUnitId(): string
    {
        return $this->fromUnitId;
    }

    public function getConvertedValues(): array
    {
        return $this->convertedValues;
    }
}
