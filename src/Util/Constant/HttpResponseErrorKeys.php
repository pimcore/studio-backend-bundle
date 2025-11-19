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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum HttpResponseErrorKeys: string
{
    use EnumToValueArrayTrait;

    case GENERIC_ERROR = 'error_something_generic_went_wrong';
    case ELEMENT_EXISTS = 'error_element_exists';
    case ELEMENT_NOT_FOUND = 'error_element_not_found';
    case ELEMENT_VALIDATION_FAILED = 'error_element_validation_failed';
    case ENVIRONMENT_ERROR = 'error_environment';
    case FOLDER_EXISTS = 'error_folder_exists';
    case CONFIG_NAME_INVALID = 'error_config_name_invalid';
    case WIDGET_NAME_MISSING = 'error_widget_name_missing';
    case INVALID_ARGUMENT = 'error_invalid_argument';
    case INVALID_ASSET_TYPE = 'error_inconsistent_asset_type';
    case VALIDATION_FAILED = 'error_validation_failed';
    case LOGIN_TOKEN_NON_ADMIN = 'error_login_token_as_admin_non_admin_user';
    case LOGIN_TOKEN_NO_PASSWORD = 'error_login_token_no_user_password';
}
