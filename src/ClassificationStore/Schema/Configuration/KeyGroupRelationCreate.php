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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\AbsentValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function get_debug_type;
use function sprintf;

/**
 * The three per assignment properties are optional in both directions: one absent from the payload
 * leaves the stored value untouched, one carrying an explicit null clears it. Their defaults
 * therefore stand for "the payload did not carry it" rather than for a value, which is why they are
 * typed `mixed` - the two states have to stay distinguishable, and the payload mapping substitutes
 * the default for a missing key.
 *
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
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private int $keyId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private int $groupId,
        #[Property(description: 'Sort order of the relation', type: 'integer', example: 0)]
        private mixed $sorter = 0,
        #[Property(description: 'Whether the key is mandatory in this group', type: 'boolean', example: false)]
        private mixed $mandatory = false,
        #[Property(
            description: 'Partial field definition overriding the one of the key for this assignment',
            type: 'object',
            example: ['defaultUnit' => 'kg'],
            nullable: true
        )]
        private mixed $definitionOverride = AbsentValue::ABSENT,
        #[Property(
            description: 'Sub group of the store this assignment belongs to',
            type: 'string',
            example: 'dimensions',
            nullable: true
        )]
        private mixed $subGroup = AbsentValue::ABSENT,
        #[Property(
            description: 'Metadata carried by this assignment',
            type: 'object',
            example: ['source' => 'erp'],
            nullable: true
        )]
        private mixed $metadata = AbsentValue::ABSENT,
    ) {
        $this->validate('sorter', $this->sorter, 'int', false);
        $this->validate('mandatory', $this->mandatory, 'bool', false);
        $this->validate('definitionOverride', $this->definitionOverride, 'array', true);
        $this->validate('subGroup', $this->subGroup, 'string', true);
        $this->validate('metadata', $this->metadata, 'array', true);
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

    public function hasDefinitionOverride(): bool
    {
        return $this->definitionOverride !== AbsentValue::ABSENT;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDefinitionOverride(): ?array
    {
        return $this->definitionOverride;
    }

    public function hasSubGroup(): bool
    {
        return $this->subGroup !== AbsentValue::ABSENT;
    }

    public function getSubGroup(): ?string
    {
        return $this->subGroup;
    }

    public function hasMetadata(): bool
    {
        return $this->metadata !== AbsentValue::ABSENT;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validate(string $property, mixed $value, string $type, bool $nullable): void
    {
        if ($value === AbsentValue::ABSENT) {
            return;
        }

        if ($value === null) {
            if ($nullable) {
                return;
            }

            throw new InvalidArgumentException(sprintf('%s cannot be null.', $property));
        }

        if (get_debug_type($value) !== $type) {
            throw new InvalidArgumentException(sprintf('%s has to be of type %s.', $property, $type));
        }
    }
}
