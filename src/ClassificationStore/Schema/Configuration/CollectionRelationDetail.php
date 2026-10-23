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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassificationStoreConfigurationCollectionRelationDetail',
    title: 'Classification Store Configuration Collection Relation Detail',
    required: ['id', 'colId', 'groupId', 'sorter', 'groupName', 'groupDescription'],
    type: 'object'
)]
final class CollectionRelationDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the relation', type: 'string', example: '1-1')]
        private readonly string $id,
        #[Property(description: 'ID of the collection', type: 'integer', example: 1)]
        private readonly int $colId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private readonly int $groupId,
        #[Property(description: 'Sort order of the relation', type: 'integer', example: 0)]
        private readonly int $sorter,
        #[Property(description: 'Name of the group', type: 'string', example: 'My Group')]
        private readonly ?string $groupName = null,
        #[Property(description: 'Description of the group', type: 'string', example: 'Group description')]
        private readonly ?string $groupDescription = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getColId(): int
    {
        return $this->colId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function getGroupDescription(): ?string
    {
        return $this->groupDescription;
    }
}
