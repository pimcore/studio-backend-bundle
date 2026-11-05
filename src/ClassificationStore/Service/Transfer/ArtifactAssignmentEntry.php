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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

/**
 * One entry of the artifact's `keyGroupRelations` section: the own columns of
 * `classificationstore_relations`, and the identifiers of the two endpoints it names.
 *
 * The three per assignment columns carry a supplied flag of their own, because the model writes
 * them only once they were loaded or explicitly set - an entry that leaves one out leaves the
 * stored value alone, and one that carries it as null clears it.
 *
 * @internal
 */
final readonly class ArtifactAssignmentEntry
{
    /**
     * @param array<string, mixed>|null $metadata
     * @param array<string, mixed>|null $definitionOverride
     */
    public function __construct(
        private string $groupIdentifier,
        private string $keyIdentifier,
        private int $sorter,
        private bool $mandatory,
        private ?array $metadata = null,
        private ?array $definitionOverride = null,
        private ?string $subGroup = null,
        private bool $metadataSupplied = false,
        private bool $definitionOverrideSupplied = false,
        private bool $subGroupSupplied = false,
    ) {
    }

    public function getGroupIdentifier(): string
    {
        return $this->groupIdentifier;
    }

    public function getKeyIdentifier(): string
    {
        return $this->keyIdentifier;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDefinitionOverride(): ?array
    {
        return $this->definitionOverride;
    }

    public function getSubGroup(): ?string
    {
        return $this->subGroup;
    }

    /**
     * A non-null value counts as supplied on its own, so constructing this entry directly - without
     * the parser that sets the flag - cannot silently drop it. The flag is what tells a field
     * supplied as null apart from an omitted one, which inference cannot recover.
     */
    public function isMetadataSupplied(): bool
    {
        return $this->metadataSupplied || $this->metadata !== null;
    }

    public function isDefinitionOverrideSupplied(): bool
    {
        return $this->definitionOverrideSupplied || $this->definitionOverride !== null;
    }

    public function isSubGroupSupplied(): bool
    {
        return $this->subGroupSupplied || $this->subGroup !== null;
    }

    /**
     * The same entry carrying the given metadata, which is how a created assignment is handed the
     * search index flag of its key ({@see ArtifactEntryWriterInterface::writeKeyAssignment()}).
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            groupIdentifier: $this->groupIdentifier,
            keyIdentifier: $this->keyIdentifier,
            sorter: $this->sorter,
            mandatory: $this->mandatory,
            metadata: $metadata,
            definitionOverride: $this->definitionOverride,
            subGroup: $this->subGroup,
            metadataSupplied: true,
            definitionOverrideSupplied: $this->definitionOverrideSupplied,
            subGroupSupplied: $this->subGroupSupplied,
        );
    }
}
