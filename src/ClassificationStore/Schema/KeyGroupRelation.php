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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Classification Store KeyGroupRelation',
    required: [
        'keyId',
        'groupId',
        'keyName',
        'groupName',
        'keyDescription',
        'groupDescription',
    ],
    type: 'object'
)]
final class KeyGroupRelation implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key ID', type: 'integer', example: 42)]
        private readonly int $keyId,
        #[Property(description: 'Group ID', type: 'integer', example: 42)]
        private readonly int $groupId,
        #[Property(description: 'Key Name', type: 'string', example: 'value')]
        private readonly string $keyName,
        #[Property(description: 'Group Name', type: 'string', example: 'value')]
        private readonly string $groupName,
        #[Property(description: 'Key Description', type: 'string', example: 'value', nullable: true)]
        private readonly ?string $keyDescription,
        #[Property(description: 'Key Description', type: 'string', example: 'value', nullable: true)]
        private readonly ?string $groupDescription,
        #[Property(
            description: 'Per-language flag as the key stores it; null defers to the class definition field',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private readonly ?bool $localized = null,
        #[Property(
            description: 'Data type of the key, as the relation stores it; null where the relation states none',
            type: 'string',
            example: 'input',
            nullable: true
        )]
        private readonly ?string $keyType = null,
        #[Property(
            description: 'Whether a grid filter can be applied to this key',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private readonly ?bool $filterable = null,
        #[Property(
            description: 'Why the key cannot be filtered:'
                . ' indexExcluded, typeUnsupported or resolutionFailed; null otherwise',
            type: 'string',
            example: 'indexExcluded',
            nullable: true
        )]
        private readonly ?string $filterUnavailableReason = null,
        #[Property(
            description: 'Label of the key in the label language, else its name; null if the key is gone',
            type: 'string',
            example: 'Height',
            nullable: true
        )]
        private readonly ?string $keyTitle = null,
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

    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function getKeyDescription(): ?string
    {
        return $this->keyDescription;
    }

    public function getGroupDescription(): ?string
    {
        return $this->groupDescription;
    }

    public function getLocalized(): ?bool
    {
        return $this->localized;
    }

    public function getKeyType(): ?string
    {
        return $this->keyType;
    }

    public function getFilterable(): ?bool
    {
        return $this->filterable;
    }

    public function getFilterUnavailableReason(): ?string
    {
        return $this->filterUnavailableReason;
    }

    public function getKeyTitle(): ?string
    {
        return $this->keyTitle;
    }
}
