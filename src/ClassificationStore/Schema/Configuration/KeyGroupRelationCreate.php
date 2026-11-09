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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\JsonObjectShapeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function get_debug_type;
use function sprintf;

/**
 * Every property but the pair is optional: one absent from the payload leaves the stored value
 * untouched, and a supplied one replaces it rather than merging into it. The three per assignment
 * ones are optional in both directions, so one carrying an explicit null clears it; `sorter` and
 * `mandatory` cannot be cleared and refuse an explicit null.
 *
 * All five defaults therefore stand for "the payload did not carry it" rather than for a value,
 * which is why they are typed `mixed` - the two states have to stay distinguishable, and the
 * payload mapping substitutes the default for a missing key. `sorter` and `mandatory` used to
 * default to `0` and `false`, which made a payload naming neither reset them on an already stored
 * assignment; only a new assignment takes those values now, and it takes them from the repository.
 *
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationKeyGroupRelationCreate',
    title: 'Classification Store Configuration Key Group Relation Create',
    required: ['keyId', 'groupId'],
    type: 'object'
)]
final readonly class KeyGroupRelationCreate
{
    use JsonObjectShapeTrait;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private int $keyId,
        #[Property(description: 'ID of the group', type: 'integer', example: 1)]
        private int $groupId,
        #[Property(
            description: 'Sort order of the relation. Omitting it keeps the stored one, 0 on a new relation',
            type: 'integer',
            example: 0
        )]
        private mixed $sorter = AbsentValue::ABSENT,
        #[Property(
            description: 'Whether the key is mandatory here. Omitting it keeps the stored flag, false if new',
            type: 'boolean',
            example: false
        )]
        private mixed $mandatory = AbsentValue::ABSENT,
        #[Property(
            description: 'Partial field definition overriding the one of the key for this assignment. Omitting it'
                . ' leaves a stored one alone, a supplied object replaces it whole rather than merging into it, and'
                . ' an explicit null clears it.',
            type: 'object',
            example: ['defaultUnit' => 'kg'],
            nullable: true
        )]
        private mixed $definitionOverride = AbsentValue::ABSENT,
        #[Property(
            description: 'Sub group of the store this assignment belongs to. Omitting it leaves a stored one alone, a'
                . ' supplied value replaces it, and an explicit null clears it.',
            type: 'string',
            example: 'dimensions',
            nullable: true
        )]
        private mixed $subGroup = AbsentValue::ABSENT,
        #[Property(
            description: 'Free-form metadata carried by this assignment. No member is reserved. Omitting it keeps a'
                . ' stored object alone, a supplied object replaces it whole rather than merging into it - so one'
                . ' rebuilt from a form without a member drops that member - and an explicit null clears it'
                . ' altogether. A JSON array is refused, the member being an object, and so is the one object an'
                . ' array cannot be told apart from once decoded - whose member names are the consecutive'
                . ' integers from zero, such as {"0": "a"} - because the stored form cannot carry that'
                . ' distinction either.',
            type: 'object',
            example: ['source' => 'erp'],
            nullable: true
        )]
        private mixed $metadata = AbsentValue::ABSENT,
    ) {
        $this->validate('sorter', $this->sorter, 'int', false);
        $this->validate('mandatory', $this->mandatory, 'bool', false);
        $this->validateJsonObject('definitionOverride', $this->definitionOverride);
        $this->validate('subGroup', $this->subGroup, 'string', true);
        $this->validateJsonObject('metadata', $this->metadata);
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function hasSorter(): bool
    {
        return $this->sorter !== AbsentValue::ABSENT;
    }

    /**
     * Carries a value only when {@see self::hasSorter()} reports one.
     */
    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function hasMandatory(): bool
    {
        return $this->mandatory !== AbsentValue::ABSENT;
    }

    /**
     * Carries a value only when {@see self::hasMandatory()} reports one.
     */
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
     * The two `type: 'object'` members, which `get_debug_type() === 'array'` does not decide: PHP
     * carries a JSON list as an array too, so a payload such as `{"metadata": ["erp"]}` would pass
     * that read and be stored under a member the contract says is an object
     * ({@see JsonObjectShapeTrait}).
     *
     * @throws InvalidArgumentException
     */
    private function validateJsonObject(string $property, mixed $value): void
    {
        if ($value === AbsentValue::ABSENT || $value === null) {
            return;
        }

        if (!$this->isJsonObject($value)) {
            throw new InvalidArgumentException(sprintf('%s has to be an object.', $property));
        }
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
