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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'User Object Dependencies',
    description: 'User Object Dependencies',
    required: ['hasHidden', 'dependencies'],
    type: 'object',
)]
final readonly class ObjectDependencies
{
    public function __construct(
        #[Property(description: 'Dependencies to objects', type: 'array', items: new Items(ref: Dependency::class))]
        private array $dependencies,
        #[Property(description: 'If is has hidden dependencies', type: 'boolean', example: true)]
        private bool $hasHidden
    ) {
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isHasHidden(): bool
    {
        return $this->hasHidden;
    }
}
