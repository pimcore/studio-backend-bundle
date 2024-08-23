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

/**
 * @internal
 */
trait ColumnsAsArrayTrait
{
    public function getColumnsAsArray(): array
    {
        return array_map(
            fn (ColumnSchema $column) => $column->toArray(),
            $this->columns
        );
    }
}
