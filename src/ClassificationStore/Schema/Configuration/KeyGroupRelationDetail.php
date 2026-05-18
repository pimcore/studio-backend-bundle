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
    schema: 'ClassificationStoreConfigurationKeyGroupRelationDetail',
    title: 'Classification Store Configuration Key Group Relation Detail',
    required: ['keyId', 'groupId', 'sorter', 'mandatory', 'keyName', 'keyDescription', 'groupName'],
    type: 'object'
)]
final class KeyGroupRelationDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private readonly int $keyId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private readonly int $groupId,
        #[Property(description: 'Sort order of the relation', type: 'integer', example: 0)]
        private readonly int $sorter,
        #[Property(description: 'Whether the key is mandatory in this group', type: 'boolean', example: false)]
        private readonly bool $mandatory,
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key')]
        private readonly ?string $keyName = null,
        #[Property(description: 'Description of the key', type: 'string', example: 'Key description')]
        private readonly ?string $keyDescription = null,
        #[Property(description: 'Name of the group', type: 'string', example: 'My Group')]
        private readonly ?string $groupName = null,
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

    public function getKeyName(): ?string
    {
        return $this->keyName;
    }

    public function getKeyDescription(): ?string
    {
        return $this->keyDescription;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }
}
