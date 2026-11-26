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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;

/**
 * A parsed store configuration artifact: the store entry, and every section keyed by what matches
 * an entry to a row - the identifier for an entity, the identifier pair for a relation.
 *
 * @internal
 */
final readonly class ArtifactDocument
{
    /**
     * @param array<string, array<string, array<string, mixed>>> $sections
     */
    public function __construct(
        private string $storeIdentifier,
        private string $storeName,
        private ?string $storeDescription,
        private array $sections,
    ) {
    }

    public function getStoreIdentifier(): string
    {
        return $this->storeIdentifier;
    }

    public function getStoreName(): string
    {
        return $this->storeName;
    }

    public function getStoreDescription(): ?string
    {
        return $this->storeDescription;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEntries(ArtifactSection $section): array
    {
        return $this->sections[$section->value] ?? [];
    }
}
