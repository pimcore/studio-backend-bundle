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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Filter\TreePqlServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ParentIdParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PqlParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFiltersParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function is_string;

/**
 * @internal
 */
final readonly class PqlFilter implements FilterInterface
{
    public function __construct(
        private TreePqlServiceInterface $treePqlService,
    ) {
    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SimpleColumnFiltersParameterInterface &&
            !$parameters instanceof PqlParameterInterface
        ) {
            return $query;
        }

        $filterValue = $this->getFilterValue($parameters);
        if ($filterValue === null) {
            return $query;
        }

        if ($parameters instanceof ParentIdParameterInterface && $parameters->getParentId() !== null) {
            return $this->applyTreePqlFilter($parameters->getParentId(), $filterValue, $query);
        }

        return $query->filterByPql($filterValue);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getFilterValue(
        SimpleColumnFiltersParameterInterface|PqlParameterInterface $parameters,
    ): ?string {
        if ($parameters instanceof PqlParameterInterface) {
            return $parameters->getPqlQuery();
        }

        $filter = $parameters->getSimpleColumnFilterByType(ColumnType::SYSTEM_PQL_QUERY->value);
        if (!$filter) {
            return null;
        }

        $filterValue = $filter->getFilterValue();
        if (!is_string($filterValue)) {
            throw new InvalidArgumentException('Invalid PQL filter. Filter value must be a string.');
        }

        return $filterValue;
    }

    private function applyTreePqlFilter(
        int $parentId,
        string $pqlQuery,
        QueryInterface $query,
    ): QueryInterface {
        $elementType = $this->resolveElementType($query);
        if ($elementType === null) {
            return $query->filterByPql($pqlQuery);
        }

        $relevantFolderKeys = $this->treePqlService->getRelevantChildFolderKeys(
            $parentId,
            $pqlQuery,
            $elementType,
        );

        return $query->filterByTreePql($pqlQuery, $relevantFolderKeys);
    }

    private function resolveElementType(QueryInterface $query): ?string
    {
        return match (true) {
            $query instanceof AssetQueryInterface => ElementTypes::TYPE_ASSET,
            $query instanceof DataObjectQueryInterface => ElementTypes::TYPE_OBJECT,
            $query instanceof DocumentQueryInterface => ElementTypes::TYPE_DOCUMENT,
            default => null,
        };
    }
}
