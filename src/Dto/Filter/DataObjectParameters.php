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

namespace Pimcore\Bundle\StudioApiBundle\Dto\Filter;

final readonly class DataObjectParameters extends Parameters implements DataObjectParametersInterface
{
    public function __construct(
        int $page = 1,
        int $pageSize = 10,
        ?int $parentId = null,
        ?string $idSearchTerm = null,
        ?string $excludeFolders = null,
        ?string $path = null,
        ?string $pathIncludeParent = null,
        ?string $pathIncludeDescendants = null,
        private ?string $classId = null
    ) {
        parent::__construct(
            $page,
            $pageSize,
            $parentId,
            $idSearchTerm,
            $excludeFolders,
            $path,
            $pathIncludeParent,
            $pathIncludeDescendants
        );
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }
}