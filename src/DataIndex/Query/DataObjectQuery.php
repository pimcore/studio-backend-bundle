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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use Carbon\Carbon;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\BooleanFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\ExcludeFoldersFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IdsFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IntegerFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\NumberFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\ClassificationStoreFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\DateFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\MultiSelectFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ClassIdsFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\TagFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\ElementKeySearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\FullTextSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\QueryLanguage\PqlFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\OrderByField;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByIndexField;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

final class DataObjectQuery implements DataObjectQueryInterface
{
    public const string DATA_OBJECT_QUERY_ID = 'data_object_query';

    public function __construct(
        private readonly DataObjectSearch $search,
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver
    ) {
    }

    public function setPage(int $page): self
    {
        $this->search->setPage($page);

        return $this;
    }

    public function setPageSize(int $pageSize): self
    {
        $this->search->setPageSize($pageSize);

        return $this;
    }

    public function filterParentId(?int $parentId): self
    {
        if ($parentId !== null) {
            $this->search->addModifier(new ParentIdFilter($parentId));
        }

        return $this;
    }

    public function filterPath(string $path, bool $includeDescendants, bool $includeParent): self
    {
        $this->search->addModifier(new PathFilter($path, !$includeDescendants, $includeParent));

        return $this;
    }

    public function setSearchTerm(?string $term): self
    {
        if ($term !== null) {
            $this->search->addModifier(new ElementKeySearch($term));
        }

        return $this;
    }

    public function excludeFolders(): self
    {
        $this->search->addModifier(new ExcludeFoldersFilter());

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setClassDefinitionName(string $classDefinitionId): self
    {
        $classDefinition = $this->classDefinitionResolver->getByName($classDefinitionId);
        if ($classDefinition === null) {
            throw new NotFoundException('Class definition', $classDefinitionId);
        }
        $this->search->setClassDefinition($classDefinition);

        return $this;
    }

    public function setClassDefinitionIds(array $classDefinitionIds): self
    {
        $this->search->addModifier(new ClassIdsFilter($classDefinitionIds, true));

        return $this;
    }

    public function getSearch(): DataObjectSearch
    {
        return $this->search;
    }

    public function orderByPath(string $direction): self
    {
        $this->search->addModifier(new OrderByFullPath(SortDirection::tryFrom($direction)));

        return $this;
    }

    public function searchById(int $id): self
    {
        $this->search->addModifier(new IdFilter($id));

        return $this;
    }

    public function searchByIds(array $ids): self
    {
        $this->search->addModifier(new IdsFilter($ids));

        return $this;
    }

    public function orderByIndex(): self
    {
        $this->search->addModifier(new OrderByIndexField());

        return $this;
    }

    /**
     * @param array<int> $tags
     */
    public function filterTags(array $tags, bool $considerChildTags): self
    {
        $this->search->addModifier(new TagFilter($tags, $considerChildTags));

        return $this;
    }

    public function filterByPql(string $pqlQuery): self
    {
        $this->search->addModifier(new PqlFilter($pqlQuery));

        return $this;
    }

    public function setUser(UserInterface $user): QueryInterface
    {
        /** @var User $user */
        $this->search->setUser($user);

        return $this;
    }

    public function filterInteger(string $field, int $value): QueryInterface
    {
        $this->search->addModifier(new IntegerFilter($field, $value));

        return $this;
    }

    public function filterFullText(string $value): QueryInterface
    {
        $this->search->addModifier(new FullTextSearch($value));

        return $this;
    }

    public function orderByField(string $fieldName, SortDirection $direction): self
    {
        $this->search->addModifier(new OrderByField($fieldName, $direction));

        return $this;
    }

    public function wildcardSearch(
        string $fieldName,
        string $searchTerm,
        bool $enablePqlFieldNameResolution = true
    ): self {
        $this->search->addModifier(new WildcardSearch($fieldName, $searchTerm, $enablePqlFieldNameResolution));

        return $this;
    }

    public function filterDatetime(
        string $field,
        Carbon|int|null $startDate = null,
        Carbon|int|null $endDate = null,
        Carbon|int|null $onDate = null,
        bool $roundToDay = true,
        bool $enablePqlFieldNameResolution = true
    ): self {
        $this->search->addModifier(new DateFilter(
            $field,
            $startDate,
            $endDate,
            $onDate,
            $roundToDay,
            $enablePqlFieldNameResolution
        ));

        return $this;
    }

    public function booleanFilter(string $fieldName, null|bool $value): self
    {
        $this->search->addModifier(new BooleanFilter($fieldName, $value));

        return $this;
    }

    public function classificationStoreFilter(
        string $fieldName,
        string $group,
        BooleanFilter|
        DateFilter|
        FullTextSearch|
        IntegerFilter|
        MultiSelectFilter|
        NumberRangeFilter|
        WildcardSearch $subModifier,
        ?string $locale = null
    ): self {
        if ($locale === null) {
            $this->search->addModifier(
                new ClassificationStoreFilter(
                    $fieldName,
                    $group,
                    $subModifier,
                )
            );

            return $this;
        }

        $this->search->addModifier(
            new ClassificationStoreFilter(
                $fieldName,
                $group,
                $subModifier,
                $locale
            )
        );

        return $this;
    }

    public function filterNumber(
        string $fieldName,
        int|float $searchTerm,
        bool $enablePqlFieldNameResolution = true
    ): self {
        $this->search->addModifier(new NumberFilter($fieldName, $searchTerm, $enablePqlFieldNameResolution));

        return $this;
    }

    public function filterNumberRange(
        string $fieldName,
        int|float|null $min = null,
        int|float|null $max = null,
        bool $enablePqlFieldNameResolution = true
    ): self {
        $this->search->addModifier(new NumberRangeFilter($fieldName, $min, $max, $enablePqlFieldNameResolution));

        return $this;
    }
}
