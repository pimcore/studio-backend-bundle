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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Permissions;

#[Schema(
    title: 'Element',
    type: 'object'
)]
class Element
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        private readonly int $id,
        #[Property(description: 'ID of parent', type: 'integer', example: 1)]
        private readonly int $parentId,
        #[Property(description: 'path', type: 'string', example: '/path/to/element')]
        private readonly string $path,
        #[Property(description: 'ID of owner', type: 'integer', example: 1)]
        private readonly int $userOwner,
        #[Property(description: 'User that modified the element', type: 'integer', example: 1)]
        private readonly int $userModification,
        #[Property(description: 'Locked', type: 'string', example: 'locked')]
        private readonly ?string $locked,
        #[Property(description: 'Is locked', type: 'boolean', example: false)]
        private readonly bool $isLocked,
        #[Property(description: 'Creation date', type: 'integer', example: 221846400)]
        private readonly ?int $creationDate,
        #[Property(description: 'Modification date', type: 'integer', example: 327417600)]
        private readonly ?int $modificationDate,
        #[Property(ref: Permissions::class)]
        private readonly Permissions $permissions
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getUserOwner(): int
    {
        return $this->userOwner;
    }

    public function getLocked(): ?string
    {
        return $this->locked;
    }

    public function getIsLocked(): bool
    {
        return $this->isLocked;
    }

    public function getPermissions(): Permissions
    {
        return $this->permissions;
    }
}
