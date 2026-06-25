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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\ShareOptionsInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;

/**
 * @internal
 */
interface ConfigurationParameterInterface extends ShareOptionsInterface
{
    public function getPageSize(): int;

    public function getName(): string;

    public function getDescription(): ?string;

    public function setAsFavorite(): bool;

    public function saveFilter(): bool;

    /**
     * @return ColumnSchema[]|Column[]
     */
    public function getColumns(): array;

    public function getColumnsAsArray(): array;

    public function getFilter(): ?Filter;
}
