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
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\SearchGridParameter;

/**
 * @internal
 * 
 * -----
 * NOTES
 * -----
 * Available Fields seen in UI:
 * - owner (User)
 * - modification date (Date)
 * - name (Textinput)
 * - description (Textarea)
 * - createShortcut (Checkbox, shortcut handling tbd)
 * - shares (selection of user and/or roles to share the Search with, also tbd)
 * 
 * Other parts that affect the search:
 * - type filter (different between element types) -> handled by SearchGridParameter
 * - search input field -> handled by SearchGridParameter
 * - advanced search & filter (handled by grid configuration already?) -> handled by SearchGridParameter
 * - tag filters -> handled by SearchGridParameter
 * - grid configuration
 */
#[ORM\Entity]
#[ORM\Table(name: SavedSearchConfiguration::TABLE_NAME)]
class SavedSearchConfiguration
{
    public const string TABLE_NAME = 'bundle_studio_saved_search_configurations';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private int $owner;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'boolean')]
    private bool $shareGlobal = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $classId = null;

    #[ORM\Column(type: 'boolean')]
    private bool $createMenuShortcut = false;

    #[ORM\Column(name: 'columns', type: 'json')]
    private array $columns;

    #[ORM\Column(name: 'columns', type: 'json')]
    private SearchGridParameter $searchParameters;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $creationDate;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $modificationDate;

    #[ORM\OneToMany(
        targetEntity: savedsearchConfigurationShare::class,
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

    public function isCreateMenuShortcut(): bool
    {
        return $this->createMenuShortcut;
    }

    public function getColumns(): array
    {
        return $this->columns;
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

    public function getSearchParameters(): SearchGridParameter
    {
        return $this->searchParameters;
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

    public function setCreateMenuShortcut(bool $createMenuShortcut): void
    {
        $this->createMenuShortcut = $createMenuShortcut;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
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

    public function addShare(SavedSearchConfigurationShare $share): void
    {
        $this->shares->add($share);
    }

    public function clearShares(): void
    {
        $this->shares->clear();
    }

    public function setSearchParameters(SearchGridParameter $searchParameters): void
    {
        $this->searchParameters = $searchParameters;
    }
}
