<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;

interface QueryInterface
{
    public function setPage(int $page): self;

    public function setPageSize(int $pageSize): self;

    public function filterParentId(?int $parentId): self;

    public function filterPath(string $path, bool $includeDescendants, bool $includeParent);

    public function setSearchTerm(?string $term): self;

    public function excludeFolders(): self;

    public function getSearch(): SearchInterface;
}