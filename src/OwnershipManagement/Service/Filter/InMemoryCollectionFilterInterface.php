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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Filter;

use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * Applies the standard collection filtering (free-text search, deleted-owner filter, sorting, and
 * pagination) to an already hydrated set of configurations.
 */
interface InMemoryCollectionFilterInterface
{
    /**
     * @param OwnershipConfiguration[] $items
     *
     * @return Collection<OwnershipConfiguration>
     */
    public function apply(array $items, OwnershipListQuery $query): Collection;
}
