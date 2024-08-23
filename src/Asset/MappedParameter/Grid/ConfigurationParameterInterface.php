<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;

/**
 * @internal
 */
interface ConfigurationParameterInterface
{
    public function getPageSize(): int;

    public function getName(): string;

    public function getDescription(): string;

    public function shareGlobal(): bool;

    public function setAsFavorite(): bool;

    public function saveFilter(): bool;

    public function getSharedUsers(): array;

    public function getSharedRoles(): array;

    /**
     * @return ColumnSchema[]
     */
    public function getColumns(): array;

    public function getColumnsAsArray(): array;

    public function getFilter(): ?Filter;
}