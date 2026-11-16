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

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Model\Element\ElementInterface;

/**
 * A resolver whose cell has a key and type of its own, which a cell denied by the language
 * permission keeps so that a client matches it to its column as it would an allowed one.
 */
interface DeniedColumnDataResolverInterface
{
    /**
     * The cell as the resolver gives it, without the value and without reading one. Null when the
     * resolver cannot address a cell for the column.
     */
    public function resolveDeniedForCoreElement(Column $column, ElementInterface $element): ?ColumnData;
}
