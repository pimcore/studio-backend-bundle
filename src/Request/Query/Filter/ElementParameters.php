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

namespace Pimcore\Bundle\StudioApiBundle\Request\Query\Filter;


/**
 * @internal
 */
readonly class ElementParameters extends CollectionParameters implements ElementParametersInterface
{
    public function __construct(
        int $page = 1,
        int $pageSize = 10,
        private ?int $parentId = null,
        private ?string $idSearchTerm = null,
        private ?string $excludeFolders = null,
        private ?string $path = null,
        private ?string $pathIncludeParent = null,
        private ?string $pathIncludeDescendants = null
    ) {
        parent::__construct($page, $pageSize);
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getIdSearchTerm(): ?string
    {
        return $this->idSearchTerm;
    }

    public function getExcludeFolders(): ?bool
    {
        return $this->excludeFolders === 'true'; // TODO: symfony 7.1 will support bool type
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getPathIncludeParent(): ?bool
    {
        return $this->pathIncludeParent === 'true'; // TODO: symfony 7.1 will support bool type
    }

    public function getPathIncludeDescendants(): ?bool
    {
        return $this->pathIncludeDescendants === 'true'; // TODO: symfony 7.1 will support bool type
    }
}
