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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationImportReport',
    title: 'Classification Store Configuration Import Report',
    required: [
        'created',
        'updated',
        'adopted',
        'matchedUnchanged',
        'targetOnly',
        'createdEntries',
        'updatedEntries',
        'adoptedEntries',
        'targetOnlyEntries',
        'conflicts',
    ],
    type: 'object'
)]
final class ImportReport implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param string[] $createdEntries
     * @param string[] $updatedEntries
     * @param string[] $adoptedEntries
     * @param string[] $targetOnlyEntries
     * @param string[] $conflicts
     * @param string[]|null $skippedEntries
     * @param string[]|null $removedEntries
     */
    public function __construct(
        #[Property(description: 'Number of entries the import created', type: 'integer', example: 3)]
        private readonly int $created,
        #[Property(description: 'Number of entries the import updated', type: 'integer', example: 1)]
        private readonly int $updated,
        #[Property(
            description: 'Number of entries that carried no identifier and adopted the one of the file',
            type: 'integer',
            example: 0
        )]
        private readonly int $adopted,
        #[Property(
            description: 'Number of entries the store already carried exactly as the file describes them',
            type: 'integer',
            example: 42
        )]
        private readonly int $matchedUnchanged,
        #[Property(
            description: 'Number of entries the store carries and the file does not; they are left in place',
            type: 'integer',
            example: 2
        )]
        private readonly int $targetOnly,
        #[Property(
            description: 'The created entries by name, the last element naming how many more there are',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['group "Dimensions"', 'and 12043 more']
        )]
        private readonly array $createdEntries,
        #[Property(
            description: 'The updated entries by name, the last element naming how many more there are',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['key "weight"']
        )]
        private readonly array $updatedEntries,
        #[Property(
            description: 'The adopted entries by name, the last element naming how many more there are',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['group "Colours"']
        )]
        private readonly array $adoptedEntries,
        #[Property(
            description: 'The entries the store carries and the file does not, the last element naming how many '
                . 'more there are',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['key assignment "Dimensions -> depth"']
        )]
        private readonly array $targetOnlyEntries,
        #[Property(
            description: 'Conflicts of the import; always empty on a successful one',
            type: 'array',
            items: new Items(type: 'string'),
            example: []
        )]
        private readonly array $conflicts,
        #[Property(
            description: 'Number of entries skipped for a name the store holds under another identifier',
            type: 'integer',
            example: 1,
            nullable: true
        )]
        private readonly ?int $skipped = null,
        #[Property(
            description: 'The skipped entries by name and reason, the last element naming how many more there are',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['group "Dimensions": the target holds the name under another identifier than the file gives it'],
            nullable: true
        )]
        private readonly ?array $skippedEntries = null,
        #[Property(
            description: 'Whether the file describes another store and replaced this store\'s configuration',
            type: 'boolean',
            example: false,
            nullable: true
        )]
        private readonly ?bool $replaced = null,
        #[Property(
            description: 'Number of entries removed before writing the file; a removed group loses its stored values',
            type: 'integer',
            example: 0,
            nullable: true
        )]
        private readonly ?int $removed = null,
        #[Property(
            description: 'The removed entries by name, the last element naming how many more there are',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['group "Dimensions"', 'key "weight"'],
            nullable: true
        )]
        private readonly ?array $removedEntries = null,
        #[Property(
            description: 'Whether the import removed, or a dry run would remove, a group and with it stored values',
            type: 'boolean',
            example: true,
            nullable: true
        )]
        private readonly ?bool $storedValuesRemoved = null,
    ) {
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getAdopted(): int
    {
        return $this->adopted;
    }

    public function getMatchedUnchanged(): int
    {
        return $this->matchedUnchanged;
    }

    public function getTargetOnly(): int
    {
        return $this->targetOnly;
    }

    /**
     * @return string[]
     */
    public function getCreatedEntries(): array
    {
        return $this->createdEntries;
    }

    /**
     * @return string[]
     */
    public function getUpdatedEntries(): array
    {
        return $this->updatedEntries;
    }

    /**
     * @return string[]
     */
    public function getAdoptedEntries(): array
    {
        return $this->adoptedEntries;
    }

    /**
     * @return string[]
     */
    public function getTargetOnlyEntries(): array
    {
        return $this->targetOnlyEntries;
    }

    /**
     * @return string[]
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function getSkipped(): ?int
    {
        return $this->skipped;
    }

    /**
     * @return string[]|null
     */
    public function getSkippedEntries(): ?array
    {
        return $this->skippedEntries;
    }

    public function isReplaced(): ?bool
    {
        return $this->replaced;
    }

    public function getRemoved(): ?int
    {
        return $this->removed;
    }

    /**
     * @return string[]|null
     */
    public function getRemovedEntries(): ?array
    {
        return $this->removedEntries;
    }

    public function isStoredValuesRemoved(): ?bool
    {
        return $this->storedValuesRemoved;
    }
}
