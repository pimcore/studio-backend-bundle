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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Grid;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: GridConfiguration::TABLE_NAME)]
class GridConfiguration
{
    public const string TABLE_NAME = 'bundle_studio_grid_configurations';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $assetFolderId = null;

    #[ORM\Column(type: 'string', nullable: true, length: 10)]
    private ?string $classId = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private int $owner;

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $pageSize;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'boolean')]
    private bool $shareGlobal = false;

    #[ORM\Column(type: 'boolean')]
    private bool $saveFilter;

    #[ORM\Column(name: 'columns', type: 'json')]
    private array $columns;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filter;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $creationDate;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $modificationDate;

    #[ORM\OneToMany(
        targetEntity: GridConfigurationShare::class,
        mappedBy: 'configuration',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $shares;

    #[ORM\OneToMany(
        targetEntity: GridConfigurationFavorite::class,
        mappedBy: 'configuration',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $favorites;

    public function __construct(
    ) {
        $this->shares = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAssetFolderId(): ?int
    {
        return $this->assetFolderId;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
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

    public function saveFilter(): bool
    {
        return $this->saveFilter;
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

    public function setAssetFolderId(int $assetFolderId): void
    {
        $this->assetFolderId = $assetFolderId;
    }

    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
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

    public function setSaveFilter(bool $saveFilter): void
    {
        $this->saveFilter = $saveFilter;
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

    public function getOwner(): int
    {
        return $this->owner;
    }

    public function setOwner(int $owner): void
    {
        $this->owner = $owner;
    }

    public function addShare(GridConfigurationShare $share): void
    {
        $this->shares->add($share);
    }

    public function addFavorite(GridConfigurationFavorite $favorite): void
    {
        $this->favorites->add($favorite);
    }

    public function clearShares(): void
    {
        $this->shares->clear();
    }

    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function removeFavorite(GridConfigurationFavorite $favorite): void
    {
        $this->favorites->removeElement($favorite);
    }

    public function isUserFavorite(UserInterface $user): bool
    {
        foreach ($this->favorites as $favorite) {
            if ($favorite->getUser() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function setClassId(?string $classId): void
    {
        $this->classId = $classId;
    }
}
