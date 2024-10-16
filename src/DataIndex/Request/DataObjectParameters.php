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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Request;

/**
 * @internal
 */
final readonly class DataObjectParameters extends ElementParameters implements ClassNameParametersInterface
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
        private ?string $className = null
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

    public function getClassName(): ?string
    {
        return $this->className;
    }
}
