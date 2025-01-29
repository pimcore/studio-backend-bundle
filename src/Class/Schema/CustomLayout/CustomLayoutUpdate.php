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
