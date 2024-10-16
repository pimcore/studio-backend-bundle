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
}
