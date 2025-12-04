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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\GetClassificationStoreFilterValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFiltersParameterInterface;
use function is_string;

/**
 * @internal
 */
final class StringFilter implements FilterInterface
{
    use GetClassificationStoreFilterValueTrait;

    public function __construct(
        private readonly GroupConfigRepositoryInterface $groupConfigRepository,
        private readonly KeyGroupRelationRepositoryInterface $keyGroupRelationRepository
    ) {

    }

    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ColumnFiltersParameterInterface) {
            return $query;
        }

        if (!$query instanceof DataObjectQueryInterface) {
            return $query;
        }

        foreach ($parameters->getColumnFilterByType(ColumnType::CLASSIFICATION_STORE_STRING->value) as $column) {

            $filterValue = $this->getClassificationStoreFilterValue($column->getFilterValue());

            $key = $this->keyGroupRelationRepository->getByKeyId($filterValue->getKeyId(), $filterValue->getGroupId());
            $group = $this->groupConfigRepository->getById($filterValue->getGroupId());

            if (!is_string($filterValue->getValue())) {
                throw new InvalidArgumentException('Filter value for this filter must be a string');
            }

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                new WildcardSearch($key->getName(), $filterValue->getValue(), true),
                $column->getLocale()
            );
        }

        return $query;
    }
}
