<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Exception;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;
use Pimcore\Model\Asset as ModelAsset;
use Pimcore\Model\Dependency;
use Pimcore\Model\User;

class Asset
{
    public function __construct(private readonly ModelAsset $asset, private readonly Permissions $permission)
    {
    }

    #[ApiProperty(genId: false)]
    public function getPermissions(): Permissions
    {
        return $this->permission;
    }

    public function getId(): ?int
    {
        return $this->asset->getId();
    }

    public function getParentId(): ?int
    {
        return $this->asset->getParentId();
    }

    public function hasChildren(): bool
    {
        return $this->asset->hasChildren();
    }

    public function getUserModification(): ?int
    {
        return $this->asset->getUserModification();
    }

    public function getCreationDate(): ?int
    {
        return $this->asset->getCreationDate();
    }

    public function getModificationDate(): ?int
    {
        return $this->asset->getModificationDate();
    }

    public function getUserOwner(): ?int
    {
        return $this->asset->getUserOwner();
    }

    /**
     * enum('self','propagate') nullable
     *
     */
    public function getLock(): ?string
    {
        return $this->asset->getLocked();
    }

    public function isLocked(): bool
    {
        return $this->asset->isLocked();
    }

    /**
     * @return Property[] the $properties
     */
    public function getProperties(): array
    {
        $properties = [];
        foreach ($this->asset->getProperties() as $property) {
            $properties[] = new Property($property);
        }

        return $properties;
    }

    public function getVersionCount(): int
    {
        return $this->asset->getVersionCount();
    }

    public function getFilename(): ?string
    {
        return $this->asset->getFilename();
    }

    public function getKey(): ?string
    {
        return $this->asset->getKey();
    }

    public function getType(): string
    {
        return $this->asset->getType();
    }

    /**
     * @return Version[] the $versions
     */
    public function getVersions(): array
    {
        $versions = [];
        foreach ($this->asset->getVersions() as $version) {
            $versions[] = new Version($version);
        }

        return $versions;
    }

    public function getCustomSettings(): array
    {
        return $this->asset->getCustomSettings();
    }

    public function getMimeType(): ?string
    {
        return $this->asset->getMimeType();
    }

    /**
     * @return Task[] the $scheduledTasks
     */
    public function getScheduledTasks(): array
    {
        $tasks = [];
        foreach ($this->asset->getScheduledTasks() as $task) {
            $tasks[] = new Task($task);
        }

        return $tasks;
    }

    public function getPath(): ?string
    {
        return $this->asset->getPath();
    }

    public function getMetadata(): array
    {
        return $this->asset->getMetadata();
    }

    #[ApiProperty(genId: false)]
    public function getDependencies(): Dependency
    {
        return $this->asset->getDependencies();
    }

    public function getHasMetaData(): bool
    {
        return $this->asset->getHasMetaData();
    }

    public function getFullPath(): string
    {
        return $this->asset->getFullPath();
    }

    public function getFrontendFullPath(): string
    {
        return $this->asset->getFrontendFullPath();
    }

    /**
     * @param User|null $user
     *
     * @return array
     *
     * @throws Exception
     */
    public function getUserPermissions(?User $user = null): array
    {
        return $this->asset->getUserPermissions($user);
    }
}
