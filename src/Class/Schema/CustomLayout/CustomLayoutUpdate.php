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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CustomLayoutUpdate',
    title: 'Schema used to update custom layouts',
    required: [
        'configuration',
        'values',
    ],
    type: 'object'
)]
final readonly class CustomLayoutUpdate
{
    public function __construct(
        #[Property(
            description: 'Layout configuration for fields (Panel, Input, ..)',
            type: 'object'
        )]
        private array $configuration,
        #[Property(description: 'Values for custom layout object itself', type: 'object')]
        private array $values
    ) {
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
