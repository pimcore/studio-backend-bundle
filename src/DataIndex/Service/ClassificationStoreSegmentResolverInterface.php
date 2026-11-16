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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;

/**
 * The path segments a Classification Store column filter addresses the search index by - the group,
 * the language node and the key, which is the whole path below the field.
 *
 * The index addresses a group and a key by their identifier, and it addresses a key under its own
 * language node, so a filter built on their names or on the grid column's language finds nothing and
 * reads as an absent value. The resolution is the index's own and is not repeated here: this asks the
 * bundle that writes it and turns the one answer it cannot use - a group or key the index holds no
 * addressable field for - into a refused filter rather than a silent empty grid.
 *
 * @internal
 */
interface ClassificationStoreSegmentResolverInterface
{
    /**
     * @throws InvalidArgumentException when the index holds no addressable group of that id
     */
    public function resolveGroupSegment(ClassificationStoreFilterValue $filterValue): string;

    /**
     * The field the sub-modifier searches: the key, or the sibling of the variant the column
     * addresses.
     *
     * @throws InvalidArgumentException when the index holds no addressable field for that key
     */
    public function resolveFieldName(ClassificationStoreFilterValue $filterValue): string;

    /**
     * The language node the key is indexed under: the column's language for a key that is localized
     * in this field, the non-localized node for one that is not. The flag is the key's own and the
     * field's only where the key carries none, which is why the field the column names has to be
     * resolved - `$parameters` names the class holding it.
     *
     * A column that names no language asks for the non-localized node, the one such a column's key
     * is written to.
     *
     * @throws InvalidArgumentException when no class of `$parameters` holds that field, so the node
     *                                  the key is written to cannot be resolved at all
     */
    public function resolveLocale(
        ClassificationStoreFilterValue $filterValue,
        ColumnFilter $column,
        mixed $parameters = null
    ): string;
}
