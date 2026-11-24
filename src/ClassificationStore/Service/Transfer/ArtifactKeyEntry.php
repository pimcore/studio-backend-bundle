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
 * One entry of the artifact's `keys` section: the own columns of `classificationstore_keys` that
 * travel, and the identifier the entry is matched by.
 *
 * The definition travels decoded, the way the key endpoints publish it, and is what carries the
 * title through a core that re-derives the column from the blob.
 *
 * @internal
 */
final readonly class ArtifactKeyEntry
{
    /**
     * @param array<string, mixed>|null $definition
     * @param array<string, mixed>|null $customMetadata
     */
    public function __construct(
        private string $identifier,
        private string $name,
        private ?string $title,
        private ?string $description,
        private string $type,
        private ?array $definition,
        private bool $enabled,
        private ?bool $localized = null,
        private ?array $customMetadata = null,
        private bool $customMetadataSupplied = false,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDefinition(): ?array
    {
        return $this->definition;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * The key's own `localized` flag, null for an entry that carries none - which is both an entry
     * written before the flag existed and one written for a key that states nothing.
     */
    public function getLocalized(): ?bool
    {
        return $this->localized;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCustomMetadata(): ?array
    {
        return $this->customMetadata;
    }

    /**
     * A non-null map counts as supplied on its own, so constructing this entry directly - without the
     * parser that sets the flag - cannot silently drop it. The flag is what tells a map supplied as
     * null apart from an omitted one, which inference cannot recover.
     */
    public function isCustomMetadataSupplied(): bool
    {
        return $this->customMetadataSupplied || $this->customMetadata !== null;
    }
}
