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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util;

/**
 * @internal
 */
enum EnvironmentVariables: string
{
    case ORIGINAL_PARENT_ID = 'originalParentId';
    case PARENT_ID = 'parentId';
    case UPLOAD_FOLDER_LOCATION = 'uploadFolderLocation';
    case UPDATE_REFERENCES = 'updateReferences';
    case REWRITE_CONFIGURATION = 'rewriteConfiguration';
    case REWRITE_PARAMETERS = 'rewriteParameters';
    case BATCH_TAG_OPERATION = 'batchTagOperation';
    case TAG_IDS = 'tagIds';
}
