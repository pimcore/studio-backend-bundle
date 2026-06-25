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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Search;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ConfigurationShareInterface;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ShareableConfigurationInterface;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: SavedSearchConfiguration::TABLE_NAME)]
class SavedSearchConfiguration implements ShareableConfigurationInterface
{
    public const string TABLE_NAME = 'bundle_studio_saved_search_configurations';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    /** @phpstan-ignore property.onlyRead */
    private int $id;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $owner;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'boolean')]
    private bool $shareGlobal = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $classId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $elementType = null;

    #[ORM\Column(type: 'boolean')]
    private bool $createMenuShortcut = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $menuShortcutGroup = null;

    #[ORM\Column(name: 'columns', type: 'json')]
    private array $columns = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filter = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $creationDate;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $modificationDate;

    #[ORM\OneToMany(
        targetEntity: SavedSearchConfigurationShare::class,
        mappedBy: 'configuration',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $shares;

    public function __construct(
    ) {
        $this->shares = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isShareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    public function isCreateMenuShortcut(): bool
    {
        return $this->createMenuShortcut;
    }

    public function getMenuShortcutGroup(): ?string
    {
        return $this->menuShortcutGroup;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilter(): ?array
    {
        return $this->filter;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function getModificationDate(): DateTime
    {
        return $this->modificationDate;
    }

    public function getOwner(): int
    {
        return $this->owner;
    }

    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setShareGlobal(bool $shareGlobal): void
    {
        $this->shareGlobal = $shareGlobal;
    }

    public function setClassId(?string $classId): void
    {
        $this->classId = $classId;
    }

    public function setElementType(?string $elementType): void
    {
        $this->elementType = $elementType;
    }

    public function setCreateMenuShortcut(bool $createMenuShortcut): void
    {
        $this->createMenuShortcut = $createMenuShortcut;
    }

    public function setMenuShortcutGroup(?string $menuShortcutGroup): void
    {
        $this->menuShortcutGroup = $menuShortcutGroup;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function setFilter(?array $filter): void
    {
        $this->filter = $filter;
    }

    public function setCreated(): void
    {
        $this->creationDate = new DateTime('now');
        $this->setModified();
    }

    public function setModified(): void
    {
        $this->modificationDate = new DateTime('now');
    }

    public function setOwner(int $owner): void
    {
        $this->owner = $owner;
    }

    public function addShare(ConfigurationShareInterface $share): void
    {
        $this->shares->add($share);
    }

    public function createShare(int $userId): SavedSearchConfigurationShare
    {
        return new SavedSearchConfigurationShare($userId, $this);
    }

    public function clearShares(): void
    {
        $this->shares->clear();
    }
}
