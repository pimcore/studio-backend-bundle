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
    case CONTEXT = 'studio_backend';

    case ASSET_NOT_FOUND_MESSAGE = 'asset_not_found';

    case USER_NOT_FOUND_MESSAGE = 'use_not_found';

    case ENVIRONMENT_VARIABLE_NOT_FOUND = 'environment_variable_not_found';
}
