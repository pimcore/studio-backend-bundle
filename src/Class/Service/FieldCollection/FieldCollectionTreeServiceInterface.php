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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionTreeNodeFolder;

/**
 * @internal
 */
interface FieldCollectionTreeServiceInterface
{
    /**
     * @param string[]|null $allowedTypes
     *
     * @return FieldCollectionTreeNode[]|FieldCollectionTreeNodeFolder[]
     */
    public function getTree(?array $allowedTypes = null): array;
}
