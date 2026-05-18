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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;

trait ColumnDataTrait
{
    private function getColumnData(
        Column $column,
        mixed $value,
        string $fieldType,
        null|InheritanceData|array $inheritanceData = null
    ): ColumnData {
        return new ColumnData($column->getKey(), $column->getLocale(), $value, $fieldType, $inheritanceData);
    }
}
