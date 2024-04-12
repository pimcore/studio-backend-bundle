<?php

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
