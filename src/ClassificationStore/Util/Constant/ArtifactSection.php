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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * The top level keys of a store configuration artifact, in the order export writes them and apply
 * reads them - every section needs the sections before it resolved.
 *
 * @internal
 */
enum ArtifactSection: string
{
    use EnumToValueArrayTrait;

    case STORE = 'store';
    case SUB_GROUPS = 'subGroups';
    case COLLECTIONS = 'collections';
    case GROUPS = 'groups';
    case KEYS = 'keys';
    case COLLECTION_GROUP_RELATIONS = 'collectionGroupRelations';
    case KEY_GROUP_RELATIONS = 'keyGroupRelations';
}
