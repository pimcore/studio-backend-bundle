<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface ClassDefinitionTreeServiceInterface
{
    /**
     * @return ClassDefinitionTreeNode[]|ClassDefinitionTreeNodeFolder[]
     */
    public function getTree(bool $grouped = true): array;

    /**
     * @throws NotFoundException
     */
    public function getClassDefinitionIdsInsideFolder(int $folderId): array;
}
