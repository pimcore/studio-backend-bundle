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

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\ExcludeFoldersFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IdsFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\TagFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\ElementKeySearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByIndexField;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

final class DataObjectQuery implements QueryInterface
{
    public const DATA_OBJECT_QUERY_ID = 'data_object_query';

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

    public function getSearch(): DataObjectSearch
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

    public function orderByIndex(): self
    {
        $this->search->addModifier(new OrderByIndexField());

        return $this;
    }

    /**
     * @param array<int> $tags
     */
    public function filterTags(array $tags, bool $considerChildTags): QueryInterface
    {
        $this->search->addModifier(new TagFilter($tags, $considerChildTags));

        return $this;
    }
}
