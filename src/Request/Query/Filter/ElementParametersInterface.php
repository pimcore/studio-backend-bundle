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
interface ElementParametersInterface
{
    public function getPage(): int;

    public function getPageSize(): int;

    public function getParentId(): ?int;

    public function getIdSearchTerm(): ?string;

    public function getExcludeFolders(): ?bool;

    public function getPath(): ?string;

    public function getPathIncludeParent(): ?bool;

    public function getPathIncludeDescendants(): ?bool;
}
