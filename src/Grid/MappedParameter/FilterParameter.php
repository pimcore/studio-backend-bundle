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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ExcludeFolderParameterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PathParameterInterface;

/**
 * @internal
 */
final class FilterParameter implements CollectionParametersInterface, ExcludeFolderParameterInterface, PathParameterInterface
{
    private ?string $path = null;

    public function __construct(
        private readonly int $page = 1,
        private readonly int $pageSize = 50,
        private readonly bool $includeDescendants = true
    ) {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getExcludeFolders(): bool
    {
        return true;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getPathIncludeParent(): bool
    {
        return false;
    }

    public function getPathIncludeDescendants(): bool
    {
        return $this->includeDescendants;
    }
}