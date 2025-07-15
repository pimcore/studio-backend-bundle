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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Model;

/**
 * @internal
 */
final readonly class RelatedElementData
{
    public function __construct(
        private int $id,
        private string $type,
        private string $subtype,
        private string $fullPath,
        private ?bool $isPublished = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSubtype(): string
    {
        return $this->subtype;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }
}
