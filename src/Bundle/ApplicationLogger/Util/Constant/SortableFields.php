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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Util\Constant;

/**
 * Sort keys the application logger collection accepts. Being a case here is what makes a key
 * allowed — anything else falls back to {@see self::ID}.
 *
 * @internal
 */
enum SortableFields: string
{
    case ID = 'id';
    case PID = 'pid';
    case TIMESTAMP = 'timestamp';
    case MESSAGE = 'message';
    case FILE_OBJECT = 'fileobject';
    case COMPONENT = 'component';
    case SOURCE = 'source';
}
