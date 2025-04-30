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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Classification Store Group Layout',
    required: [
        'groups',
    ],
    type: 'object'
)]
final readonly class CollectionLayout
{
    public function __construct(

        #[Property(description: 'Groups', type: 'array', items: new Items(ref: GroupLayout::class))]
        private array $groups = [],
    ) {
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
