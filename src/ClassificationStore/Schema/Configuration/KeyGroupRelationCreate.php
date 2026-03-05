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
    schema: 'ClassificationStoreConfigurationKeyGroupRelationCreate',
    title: 'Classification Store Configuration Key Group Relation Create',
    required: ['keyId', 'groupId', 'sorter', 'mandatory'],
    type: 'object'
)]
final readonly class KeyGroupRelationCreate
{
    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private int $keyId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private int $groupId,
        #[Property(description: 'Sort order of the relation', type: 'integer', example: 0)]
        private int $sorter = 0,
        #[Property(description: 'Whether the key is mandatory in this group', type: 'boolean', example: false)]
        private bool $mandatory = false,
    ) {
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }
}
