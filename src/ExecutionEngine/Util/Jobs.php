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

enum Jobs: string
{
    case CREATE_ZIP = 'studio_ee_job_create_download_zip';
    case CLONE_ASSETS = 'studio_ee_job_clone_assets';
    case DELETE_ASSETS = 'studio_ee_job_delete_assets';
    case BATCH_DELETE_ASSETS = 'studio_ee_job_batch_delete_assets';
    case UPLOAD_ASSETS = 'studio_ee_job_upload_assets';
    case ZIP_FILE_UPLOAD = 'studio_ee_job_upload_zip_file';
    case CREATE_CSV = 'studio_ee_job_create_csv';
    case CREATE_XLSX = 'studio_ee_job_create_xlsx';
    case PATCH_ELEMENTS = 'studio_ee_job_patch_elements';
    case CLONE_DATA_OBJECTS = 'studio_ee_job_clone_data_objects';
    case REWRITE_REFERENCES = 'studio_ee_job_rewrite_element_references';
    case DELETE_DATA_OBJECTS = 'studio_ee_job_delete_data_objects';
    case BATCH_DELETE_DATA_OBJECTS = 'studio_ee_job_batch_delete_data_objects';
    case CLONE_DOCUMENTS = 'studio_ee_job_clone_documents';
    case DELETE_DOCUMENTS = 'studio_ee_job_delete_documents';
    case BATCH_TAG_ASSIGN = 'studio_ee_job_batch_tag_assign';
    case BATCH_TAG_REPLACE = 'studio_ee_job_batch_tag_replace';
    case RECYCLE_BIN_DELETE = 'studio_ee_job_recycle_bin_delete';
    case RECYCLE_BIN_RESTORE = 'studio_ee_job_recycle_bin_restore';
    case ELEMENT_USAGE_REPLACE = 'studio_ee_job_element_usage_replace';
}
