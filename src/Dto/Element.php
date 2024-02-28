<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;

class Element
{
    public function __construct(
        private readonly int $id,
        private readonly int $parentId,
        private readonly string $path,
        private readonly int $userOwner,
        private readonly int $userModification,
        private readonly ?string $locked,
        private readonly bool $isLocked,
        private readonly ?int $creationDate,
        private readonly ?int $modificationDate,
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

    /**
     * enum('self','propagate') nullable
     *
     */
    public function getLock(): ?string
    {
        return $this->locked;
    }



//    public function getParent(): ?Element2
//    {
//        if ($this->element->getParent() === null) {
//            return null;
//        }
//        // TODO: implement proper user permission handling via service
//        return new Element2($this->element->getParent(), new Permissions());
//    }

//    /**
//     * @return Property[] the $properties
//     */
//    public function getProperties(): array
//    {
//        $properties = [];
//        foreach ($this->element->getProperties() as $property) {
//            $properties[] = new Property($property);
//        }
//
//        return $properties;
//    }


//    public function getVersionCount(): int
//    {
//        return $this->element->getVersionCount();
//    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    #[ApiProperty(genId: false)]
    public function getPermissions(): Permissions
    {
        return $this->permissions;
    }


//    #[ApiProperty(genId: false)]
//    public function getDependencies(): Dependency
//    {
//        return new Dependency($this->element->getDependencies());
//    }

//    /**
//     * @return Task[] the $scheduledTasks
//     */
//    public function getScheduledTasks(): array
//    {
//        $tasks = [];
//        foreach ($this->element->getScheduledTasks() as $task) {
//            $tasks[] = new Task($task);
//        }
//
//        return $tasks;
//    }

//    /**
//     * @return Version[] the $versions
//     */
//    public function getVersions(): array
//    {
//        $versions = [];
//        foreach ($this->element->getVersions() as $version) {
//            $versions[] = new Version($version);
//        }
//
//        return $versions;
//    }
}