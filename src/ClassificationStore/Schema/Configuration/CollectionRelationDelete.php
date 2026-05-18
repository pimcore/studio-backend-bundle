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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationCollectionRelationDelete',
    title: 'Classification Store Configuration Collection Relation Delete',
    required: ['colId', 'groupId'],
    type: 'object'
)]
final readonly class CollectionRelationDelete
{
    public function __construct(
        #[Property(description: 'ID of the collection', type: 'integer', example: 1)]
        private int $colId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private int $groupId,
    ) {
    }

    public function getColId(): int
    {
        return $this->colId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
