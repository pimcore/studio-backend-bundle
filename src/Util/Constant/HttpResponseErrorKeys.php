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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum HttpResponseErrorKeys: string
{
    use EnumToValueArrayTrait;

    case GENERIC_ERROR = 'error_something_generic_went_wrong';
    case ELEMENT_EXISTS = 'error_element_exists';
    case ELEMENT_NOT_FOUND = 'error_element_not_found';
    case ELEMENT_VALIDATION_FAILED = 'error_element_validation_failed';
    case FOLDER_EXISTS = 'error_folder_exists';
    case CONFIG_NAME_INVALID = 'error_config_name_invalid';
    case WIDGET_NAME_MISSING = 'error_widget_name_missing';
    case INVALID_ARGUMENT = 'error_invalid_argument';
    case VALIDATION_FAILED = 'error_validation_failed';
}
