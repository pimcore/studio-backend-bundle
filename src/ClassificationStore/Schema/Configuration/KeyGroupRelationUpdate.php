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
 * Every property is optional: an absent one leaves the stored value untouched, an explicit null
 * clears it. The default of each therefore stands for "the payload did not carry it" rather than for
 * a value, which is why the properties are typed `mixed` - the two states have to stay
 * distinguishable. `keyId` and `groupId` are taken from the path and are not accepted here.
 *
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationKeyGroupRelationUpdate',
    title: 'Classification Store Configuration Key Group Relation Update',
    type: 'object'
)]
final readonly class KeyGroupRelationUpdate
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'Sort order of the relation', type: 'integer', example: 0)]
        private mixed $sorter = AbsentValue::ABSENT,
        #[Property(description: 'Whether the key is mandatory in this group', type: 'boolean', example: false)]
        private mixed $mandatory = AbsentValue::ABSENT,
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

    public function hasSorter(): bool
    {
        return $this->sorter !== AbsentValue::ABSENT;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function hasMandatory(): bool
    {
        return $this->mandatory !== AbsentValue::ABSENT;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function hasDefinitionOverride(): bool
    {
        return $this->definitionOverride !== AbsentValue::ABSENT;
    }

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
