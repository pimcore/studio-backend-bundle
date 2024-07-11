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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\MetaData;

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
}
