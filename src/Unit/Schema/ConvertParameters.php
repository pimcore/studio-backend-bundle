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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Unit Convert Parameters',
    required: ['fromUnitId', 'toUnitId', 'value'],
    type: 'object'
)]
final readonly class ConvertParameters
{
    public function __construct(
        #[Property(description: 'From Unit Id', type: 'string', example: 'm')]
        private string $fromUnitId,
        #[Property(description: 'To Unit Id', type: 'string', example: 'mm')]
        private string $toUnitId,
        #[Property(description: 'Value', example: 5, anyOf: [new Schema(type: 'float'), new Schema(type: 'integer')])]
        private float|int $value,
    ) {
    }

    public function getFromUnitId(): string
    {
        return $this->fromUnitId;
    }

    public function getToUnitId(): string
    {
        return $this->toUnitId;
    }

    public function getValue(): float|int
    {
        return $this->value;
    }
}
