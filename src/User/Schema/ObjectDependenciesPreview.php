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
 * A bounded preview of a user's object dependencies, capped well below any hydration
 * cost that could matter. Use the paginated GET /user/{id}/object-dependencies endpoint
 * to browse the full, possibly much larger, list.
 *
 * @internal
 */
#[Schema(
    title: 'User Object Dependencies Preview',
    description: 'A bounded preview of DataObjects referencing this user. '
        . 'Use GET /user/{id}/object-dependencies to browse the full list.',
    required: ['totalItems', 'dependencies'],
    type: 'object',
)]
final readonly class ObjectDependenciesPreview
{
    public function __construct(
        #[Property(description: 'Total number of objects referencing this user', type: 'integer', example: 666)]
        private int $totalItems,
        #[Property(description: 'Preview of dependencies to objects', type: 'array', items: new Items(ref: Dependency::class))]
        private array $dependencies
    ) {
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
