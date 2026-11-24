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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Version\UnresolvedReference;

/**
 * @internal
 */
interface DeletedRelationServiceInterface
{
    /**
     * Adds the relations of a version to elements that were deleted afterwards to the object data of the version,
     * as entries marked with "isDeleted". Supports many-to-one, many-to-many and many-to-many object relations
     * of the class and of its localized fields.
     *
     * @param UnresolvedReference[] $references
     */
    public function addDeletedRelations(array $objectData, ClassDefinition $class, array $references): array;
}
