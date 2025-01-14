<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

/**
 * @internal
 */
final readonly class RelationData
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
