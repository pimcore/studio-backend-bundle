<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Asset\AssetMetaDataFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\ExcludeFoldersFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IdsFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\DateFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\ElementKeySearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\OrderByField;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;

final class AssetQuery implements QueryInterface
{
    public const ASSET_QUERY_ID = 'asset_query';

    public function __construct(private readonly SearchInterface $search)
    {
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

    public function getSearch(): SearchInterface
    {
        return $this->search;
    }

    public function orderByPath(string $direction): self
    {
        $this->search->addModifier(new OrderByFullPath(SortDirection::tryFrom($direction)));

        return $this;
    }

    public function searchByIds(array $ids): self
    {
        $this->search->addModifier(new IdsFilter($ids));

        return $this;
    }

    public function filterMetaData(string $name, string $type, mixed $data): self
    {
        $this->search->addModifier(new AssetMetaDataFilter($name, $type, $data));

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
        int|null $startDate = null,
        int|null $endDate = null,
        int|null $onDate = null,
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
}
