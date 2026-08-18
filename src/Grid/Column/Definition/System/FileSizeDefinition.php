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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\System;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;

/**
 * @internal
 */
final readonly class FileSizeDefinition implements ColumnDefinitionInterface
{
    public function getType(): string
    {
        return 'system.fileSize';
    }

    public function getConfig(mixed $config): array
    {
        return  [];
    }

    public function isSortable(): bool
    {
        return true;
    }

    public function getFrontendType(): string
    {
        return FrontendType::INPUT->value;
    }

    public function isExportable(): bool
    {
        return true;
    }

    public function isFilterable(): bool
    {
        return true;
    }
}
