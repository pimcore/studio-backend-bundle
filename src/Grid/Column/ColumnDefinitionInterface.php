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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column;

interface ColumnDefinitionInterface
{
    public function getType(): string;

    public function getConfig(mixed $config): array;

    public function isSortable(): bool;

    public function isFilterable(): bool;

    public function getFrontendType(): string;

    public function isExportable(): bool;
}
