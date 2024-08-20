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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class SaveConfigurationParameter
{
    public function __construct(
        #[NotBlank]
        private int $folderId,
        #[NotBlank]
        private int $pageSize,
        #[NotBlank]
        private string $name,
        #[NotBlank]
        private string $description,
        #[NotBlank]
        private array $sharedUsers,
        #[NotBlank]
        private array $sharedRoles,
        #[NotBlank]
        private array $columns,
        private ?Filter $filter = null,
        private bool $saveFilter = false,
        private bool $shareGlobal = false,
        private bool $setAsFavorite = false,
    ) {
    }

    public function getFolderId(): int
    {
        return $this->folderId;
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

    public function shareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    public function setAsFavorite(): bool
    {
        return $this->setAsFavorite;
    }

    public function saveFilter(): bool
    {
        return $this->saveFilter;
    }

    public function getSharedUsers(): array
    {
        return $this->sharedUsers;
    }

    public function getSharedRoles(): array
    {
        return $this->sharedRoles;
    }

    /**
     * @return ColumnSchema[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnsAsArray(): array
    {
        return array_map(
            fn (ColumnSchema $column) => $column->toArray(),
            $this->columns
        );
    }

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}
