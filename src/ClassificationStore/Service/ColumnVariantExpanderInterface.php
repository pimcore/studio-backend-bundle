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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;

/**
 * Expands a saved Classification Store grid column naming a `multiValue` key into the columns the grid
 * and the export carry for it, so that both carry the same columns in the same order.
 *
 * @internal
 */
interface ColumnVariantExpanderInterface
{
    /**
     * One column per variant the key declares, in the order it declares them, then one for the default slot.
     * A column that names a variant of its own, or a key that is not of type `multiValue`, is kept as it is.
     * A column addressing its key by identifiers is resolved in the class given, as its cells are, and is kept
     * as it is where no class is given or the identifiers do not resolve.
     *
     * @param array<int, Column> $columns
     *
     * @return array<int, Column>
     */
    public function expand(array $columns, ?string $classId = null): array;
}
