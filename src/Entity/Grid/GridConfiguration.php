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


namespace Pimcore\Bundle\StudioBackendBundle\Entity\Grid;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */

#[ORM\Entity]
#[ORM\Table(name: GridConfiguration::TABLE_NAME)]
class GridConfiguration
{

    public const TABLE_NAME = 'bundle_studio_grid_configurations';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $assetFolderId;

    #[ORM\Column(type: 'integer')]
    private int $owner;

    #[ORM\Column(type: 'integer')]
    private int $pageSize;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string')]
    private string $description;

    #[ORM\Column(type: 'boolean')]
    private bool $shareGlobal;

    #[ORM\Column(type: 'boolean')]
    private bool $saveFilter;

    #[ORM\Column(name: 'columns', type: 'json')]
    private array $columns;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filter;

    #[ORM\Column(type: 'datetime')]
    private DateTime $creationDate;

    #[ORM\Column(type: 'datetime')]
    private DateTime $modificationDate;

    public function __construct(
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAssetFolderId(): int
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

    public function getDescription(): string
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

    public function setDescription(string $description): void
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
        $this->creationDate = new DateTime("now");
        $this->setModified();
    }

    public function setModified(): void
    {
        $this->modificationDate = new DateTime("now");
    }

    public function getOwner(): int
    {
        return $this->owner;
    }

    public function setOwner(int $owner): void
    {
        $this->owner = $owner;
    }
}