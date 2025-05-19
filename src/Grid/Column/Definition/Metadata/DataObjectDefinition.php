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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;

/**
 * @internal
 */
final readonly class DataObjectDefinition implements ColumnDefinitionInterface
{
    public function getType(): string
    {
        return ColumnType::METADATA_DATA_OBJECT->value;
    }

    public function getConfig(mixed $config): array
    {
        return  [];
    }

    public function isSortable(): bool
    {
        return false;
    }

    public function getFrontendType(): string
    {
        return FrontendType::ELEMENT_DROPZONE->value;
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
