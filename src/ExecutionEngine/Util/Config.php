<?php

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

enum Config: string
{
    case CONTEXT_STOP_ON_ERROR = 'studio_stop_on_error';
    case CONTEXT_CONTINUE_ON_ERROR = 'studio_continue_on_error';
    case NO_ELEMENT_PROVIDED = 'studio_ee_no_element_provided';
    case NO_FOLDER_PROVIDED = 'studio_ee_no_folder_provided';
    case ELEMENT_NOT_FOUND_MESSAGE = 'studio_ee_element_not_found';
    case USER_NOT_FOUND_MESSAGE = 'studio_ee_user_not_found';
    case ENVIRONMENT_VARIABLE_NOT_FOUND = 'studio_ee_environment_variable_not_found';
    case ELEMENT_LOCKED_MESSAGE = 'studio_ee_element_locked';
    case ELEMENT_PERMISSION_MISSING_MESSAGE = 'studio_ee_element_permission_missing';
    case ELEMENT_HAS_CHILDREN_MESSAGE = 'studio_ee_element_has_existing_children';
    case ELEMENT_DELETE_FAILED_MESSAGE = 'studio_ee_element_delete_failed';
    case ELEMENT_BATCH_DELETE_FAILED_MESSAGE = 'studio_ee_element_batch_delete_failed';
    case ZIP_FILE_COPY_FAILED_MESSAGE = 'studio_ee_zip_file_copy_failed';
    case ZIP_FILE_UPLOAD_FAILED_MESSAGE = 'studio_ee_zip_file_upload_failed';
    case ZIP_CLEANUP_FAILED_MESSAGE = 'studio_ee_zip_cleanup_failed';
    case CSV_CREATION_FAILED_MESSAGE = 'studio_ee_csv_creation_failed';
    case CSV_DATA_COLLECTION_FAILED_MESSAGE = 'studio_ee_csv_data_collection_failed';
    case ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED = 'studio_ee_element_folder_collection_not_supported';
    case FILE_NOT_FOUND_FOR_JOB_RUN = 'studio_ee_file_not_found_for_job_run';
    case NO_ASSETS_FOUND_FOR_JOB_RUN = 'studio_ee_no_assets_found_for_job_run';
    case ASSET_UPLOAD_FAILED_MESSAGE = 'studio_ee_asset_upload_failed';
    case ELEMENT_PATCH_FAILED_MESSAGE = 'studio_ee_element_patch_failed';
    case NO_ELEMENT_DATA_FOUND = 'studio_ee_no_element_data_found';
    case ELEMENT_REWRITE_REFERENCES_FAILED_MESSAGE = 'studio_ee_element_rewrite_references_failed';
    case ELEMENT_TAG_OPERATION_FAILED_MESSAGE = 'studio_ee_element_tag_operation_failed';
    case INVALID_ELEMENT_TYPE_PROVIDED = 'studio_ee_invalid_element_type_provided';
    case XLSX_CREATION_FAILED_MESSAGE = 'studio_ee_xlsx_creation_failed';
    case RECYCLE_BIN_DELETE_FAILED = 'studio_ee_recycle_bin_delete_failed';
    case RECYCLE_BIN_RESTORE_FAILED = 'studio_ee_recycle_bin_restore_failed';
    case ELEMENT_REPLACE_ASSIGNMENT_FAILED = 'studio_ee_element_replace_assignment_failed';
}
