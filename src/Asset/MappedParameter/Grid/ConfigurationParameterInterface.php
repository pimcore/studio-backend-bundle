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
