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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;

/**
 * @internal
 */
final class TagFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SimpleColumnFiltersParameterInterface) {
            return $query;
        }

        $filter = $parameters->getSimpleColumnFilterByType(ColumnType::SYSTEM_TAG->value);

        if (!$filter) {
            return $query;
        }
        
        $user = $query->getSearch()->getUser();
        if (!$user || !$user->isAllowed(UserPermissions::TAGS_SEARCH->value)) {
            throw new ForbiddenException(
                sprintf(
                    'User does not have permission: %s',
                    UserPermissions::TAGS_SEARCH->value
                )
            );
        }

        $filterValue = $filter->getFilterValue();

        if (!isset($filterValue['tags'], $filterValue['considerChildTags'])) {
            throw new InvalidArgumentException('Invalid tag filter');
        }

        return $query->filterTags($filterValue['tags'], $filterValue['considerChildTags']);
    }
}
