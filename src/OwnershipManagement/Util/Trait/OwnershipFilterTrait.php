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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use function is_string;

/**
 * Extracts the single free-text search term (column filter of type "search") from the
 * generic collection FilterParameter, so every provider applies it consistently.
 *
 * @internal
 */
trait OwnershipFilterTrait
{
    private const string SEARCH_FILTER_TYPE = 'search';

    private const string INCLUDE_DELETED_OWNERS_FILTER_TYPE = 'includeDeletedOwners';

    /**
     * @throws InvalidArgumentException
     */
    private function getSearchTerm(FilterParameter $filter): ?string
    {
        $searchFilter = $filter->getSimpleColumnFilterByType(self::SEARCH_FILTER_TYPE);
        if ($searchFilter === null) {
            return null;
        }

        $value = $searchFilter->getFilterValue();
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Whether configurations owned by deleted users should be included. Defaults to true
     * (nothing is hidden) when the filter is not provided.
     *
     * @throws InvalidArgumentException
     */
    private function includeDeletedOwners(FilterParameter $filter): bool
    {
        $deletedOwnersFilter = $filter->getSimpleColumnFilterByType(self::INCLUDE_DELETED_OWNERS_FILTER_TYPE);
        if ($deletedOwnersFilter === null) {
            return true;
        }

        return (bool) $deletedOwnersFilter->getFilterValue();
    }
}
