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

use Pimcore\ValueObject\Integer\PositiveInteger;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @internal
 */
readonly class Parameters implements ParametersInterface
{
    public function __construct(
        #[NotBlank]
        #[Positive]
        private int $page = 1,
        #[NotBlank]
        #[Positive]
        private int $pageSize = 10,
        private ?int $parentId = null,
        private ?string $idSearchTerm = null,
        private ?string $excludeFolders = null,
        private ?string $path = null,
        private ?string $pathIncludeParent = null,
        private ?string $pathIncludeDescendants = null
    ) {
        $this->validate();
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
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

    private function validate(): void
    {
        new PositiveInteger($this->page);
        new PositiveInteger($this->pageSize);
    }
}
