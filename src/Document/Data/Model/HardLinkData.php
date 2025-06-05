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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Data\Model;

/**
 * @internal
 */
final readonly class HardLinkData
{
    public function __construct(
        private ?int $sourceId = null,
        private bool $childrenFromSource = false,
        private bool $propertiesFromSource = false,
        private ?string $sourcePath = null,
    ) {
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function isChildrenFromSource(): bool
    {
        return $this->childrenFromSource;
    }

    public function isPropertiesFromSource(): bool
    {
        return $this->propertiesFromSource;
    }

    public function getSourcePath(): ?string
    {
        return $this->sourcePath;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
