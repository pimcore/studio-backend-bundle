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
    title: 'ImageData',
    type: 'object'
)]
final readonly class ImageData
{
    public function __construct(
        #[Property(ref: FocalPoint::class, description: 'focalPoint', type: 'object')]
        private array $focalPoint,
    ) {
    }

    public function getFocalPoint(): array
    {
        return $this->focalPoint;
    }
}
