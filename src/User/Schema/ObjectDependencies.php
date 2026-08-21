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
    description: 'User Object Dependencies. dependencies is a bounded preview - use '
        . 'GET /user/{id}/object-dependencies to browse beyond it. totalItems counts every referencing '
        . 'object regardless of the caller\'s view permission on it, so it may exceed the number of '
        . 'objects that dependencies (or the paginated endpoint, across all pages) ever actually shows.',
    required: ['hasHidden', 'dependencies'],
    type: 'object',
)]
final readonly class ObjectDependencies
{
    public function __construct(
        #[Property(description: 'Dependencies to objects', type: 'array', items: new Items(ref: Dependency::class))]
        private array $dependencies,
        #[Property(description: 'If it has hidden dependencies', type: 'boolean', example: true)]
        private bool $hasHidden,
        #[Property(
            description: 'Total number of objects referencing this user, including ones the caller is not '
                . 'permitted to view - not the count of objects actually returned via dependencies or the '
                . 'paginated endpoint.',
            type: 'integer',
            example: 666
        )]
        private int $totalItems
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

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }
}
