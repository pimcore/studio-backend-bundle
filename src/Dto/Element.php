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

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;

class Element
{
    private ?int $parentId = null;

    private ?string $path;

    private ?int $userOwner;

    private ?int $userModification;

    private ?string $locked;

    private ?bool $isLocked;

    private ?int $creationDate;

    private ?int $modificationDate;

    private ?Permissions $permissions;

    public function __construct(
        private readonly int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function setUserOwner(?int $userOwner): void
    {
        $this->userOwner = $userOwner;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setUserModification(?int $userModification): void
    {
        $this->userModification = $userModification;
    }

    /**
     * enum('self','propagate') nullable
     */
    public function getLock(): ?string
    {
        return $this->locked;
    }

    public function setLocked(?string $locked): void
    {
        $this->locked = $locked;
    }

    public function isLocked(): ?bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(?bool $isLocked): void
    {
        $this->isLocked = $isLocked;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(?int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(?int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    #[ApiProperty(genId: false)]
    public function getPermissions(): Permissions
    {
        return $this->permissions;
    }

    public function setPermissions(Permissions $permissions): void
    {
        $this->permissions = $permissions;
    }
}
