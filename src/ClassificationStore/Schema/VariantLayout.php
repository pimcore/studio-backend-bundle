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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Classification Store Variant Layout',
    required: [
        'name',
    ],
    type: 'object'
)]
final readonly class VariantLayout
{
    public function __construct(
        #[Property(description: 'Name a value addresses the variant by', type: 'string', example: 'metric')]
        private string $name,
        #[Property(description: 'Label of the variant', type: 'string', example: 'Metric', nullable: true)]
        private ?string $label = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
