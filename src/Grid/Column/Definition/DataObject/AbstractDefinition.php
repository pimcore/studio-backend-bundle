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


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;


/**
 * @internal
 */
abstract readonly class AbstractDefinition implements ColumnDefinitionInterface
{
    public abstract function getType(): string;

    public function getConfig(mixed $config): array
    {
        return [];
    }

    public function isSortable(): bool
    {
        return true;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public abstract function getFrontendType(): string;

    public function isExportable(): bool
    {
        return true;
    }
}