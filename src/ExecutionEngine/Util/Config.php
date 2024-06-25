<?php

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

enum Config: string
{
    case CONTEXT = 'studio';
    case NO_ELEMENT_PROVIDED = 'ee_no_element_provided';
    case ELEMENT_NOT_FOUND_MESSAGE = 'ee_element_not_found';
    case USER_NOT_FOUND_MESSAGE = 'ee_user_not_found';
    case ENVIRONMENT_VARIABLE_NOT_FOUND = 'ee_environment_variable_not_found';
    case ELEMENT_LOCKED_MESSAGE = 'ee_element_locked';
    case ELEMENT_PERMISSION_MISSING_MESSAGE = 'ee_element_permission_missing';
    case ELEMENT_HAS_CHILDREN_MESSAGE = 'ee_element_has_existing_children';
    case ELEMENT_DELETE_FAILED_MESSAGE = 'ee_element_delete_failed';
}
