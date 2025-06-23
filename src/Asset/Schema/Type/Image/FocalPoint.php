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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'FocalPoint',
    required: [
        'x',
        'y',
    ],
    type: 'object'
)]
final readonly class FocalPoint
{
    public function __construct(
        #[Property(description: 'x Coordinate of FocalPoint', type: 'integer', example: 50)]
        private int $x,
        #[Property(description: 'y Coordinate of FocalPoint', type: 'integer', example: 50)]
        private int $y,
    ) {
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }
}
