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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constants;

/**
 * @internal
 */
enum UserPermissions: string
{
    case ASSETS = 'assets';
    case DOCUMENTS = 'documents';
    case OBJECTS = 'objects';
    case NOTES_EVENTS = 'notes_events';
    case THUMBNAILS = 'thumbnails';
    case USER_MANAGEMENT = 'users';
    case PIMCORE_ADMIN = 'ROLE_PIMCORE_ADMIN';
}
