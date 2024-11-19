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
