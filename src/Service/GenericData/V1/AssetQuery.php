<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\ExcludeFoldersFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\PathFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\ElementKeySearch;

final class AssetQuery
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
}
