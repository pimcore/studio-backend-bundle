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
    case PERMISSION_DENIED = 'error_permission_denied';
    case DOCUMENT_LANGUAGE_NOT_SET = 'error_document_language_not_set';
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
    case GDI_PARSING_EXCEPTION = 'error_gdi_parsing_exception';
    case UID_ALREADY_EXISTS = 'error_uid_used';
    case RECYCLE_BIN_RESTORE  = 'error_recycle_bin_restore';
    case RECYCLE_BIN_RESTORE_MISSING_PARENT  = 'error_recycle_bin_restore_missing_parent';
    case UNDECLARED_VARIANT = 'error_undeclared_variant';
    case DUPLICATE_VALUE_QUALIFIER = 'error_duplicate_value_qualifier';
    case ENGINE_CAPABILITY_MISSING = 'error_engine_capability_missing';
    case KEY_NOT_REPEATABLE = 'error_key_not_repeatable';
    case FILTER_TYPE_UNSUPPORTED = 'error_filter_type_unsupported';
    case FILTER_KEY_NOT_INDEXED = 'error_filter_key_not_indexed';
    case FILTER_VARIANT_UNDECLARED = 'error_filter_variant_undeclared';
    case FILTER_DEFINITION_UNRESOLVABLE = 'error_filter_definition_unresolvable';
}
