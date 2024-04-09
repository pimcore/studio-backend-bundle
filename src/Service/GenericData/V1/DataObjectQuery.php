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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\ExcludeFoldersFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\ElementKeySearch;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;

final class DataObjectQuery
{
    public const DATA_OBJECT_QUERY_ID = 'data_object_query';

    public function __construct(
        private readonly DataObjectSearchInterface $search,
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

    public function setClassDefinitionId(string $classDefinitionId): self
    {
        $classDefinition = $this->classDefinitionResolver->getById($classDefinitionId);

        $this->search->setClassDefinition($classDefinition);

        return $this;
    }

    public function getSearch(): DataObjectSearchInterface
    {
        return $this->search;
    }
}
