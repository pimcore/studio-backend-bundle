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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\FilterUnavailableReason;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The one place it is decided whether, and by which filter, a Classification Store key can be filtered.
 *
 * A key's type is routed by the {@see \Pimcore\Model\DataObject\Classificationstore\KeyFilter} its field
 * definition declares, so a type that gains a declaration in core is filterable without a change here.
 *
 * @internal
 */
interface KeyFilterResolverInterface
{
    /**
     * The filter types of this bundle that apply to a key of this definition, the first being the one
     * the key is routed to; none where its type declares no filter.
     *
     * @return string[]
     */
    public function getFilterTypes(Data $definition): array;

    /**
     * The filter types that apply to the key of this assignment, by the key's own definition - the type and the
     * search index flag belong to the key, so an assignment's override is not consulted; none where the
     * definition cannot be read or its type declares no filter.
     *
     * @return string[]
     */
    public function getFilterTypesOf(KeyGroupRelation $relation): array;

    /**
     * Whether any filter of this bundle consumes the filter type.
     */
    public function isSupportedFilterType(string $filterType): bool;

    /**
     * Why the key cannot be filtered, or null where it can: excluded from the search index by its own
     * definition, of a type that declares no filter, or of a definition that cannot be rebuilt.
     */
    public function getFilterUnavailableReason(KeyGroupRelation $relation): ?FilterUnavailableReason;
}
