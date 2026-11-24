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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The one place the sub group list of a group is derived from its key assignments, so that the
 * group layout endpoint and the enrichment of an already-assigned group cannot answer differently.
 *
 * @internal
 */
interface SubGroupLayoutResolverInterface
{
    /**
     * The sub groups the keys of the group carry, each positioned by the first key carrying it: the
     * relations arrive in sorter then id order, so the order follows the keys and is never stored.
     * An identifier with no row in the store's vocabulary is left out rather than fabricated from
     * the identifier.
     *
     * One statement for the whole group, instead of a vocabulary read per key.
     *
     * @param KeyGroupRelation[] $keys
     *
     * @return SubGroupLayout[]
     */
    public function resolve(array $keys, int $storeId): array;
}
