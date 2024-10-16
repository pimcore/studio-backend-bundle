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

enum Jobs: string
{
    case CREATE_ZIP = 'studio_ee_job_create_download_zip';
    case CLONE_ASSETS = 'studio_ee_job_clone_assets';
    case DELETE_ASSETS = 'studio_ee_job_delete_assets';
    case UPLOAD_ASSETS = 'studio_ee_job_upload_assets';
    case ZIP_FILE_UPLOAD = 'studio_ee_job_upload_zip_file';
    case CREATE_CSV = 'studio_ee_job_create_csv';
    case PATCH_ELEMENTS = 'studio_ee_job_patch_elements';
    case CLONE_DATA_OBJECTS = 'studio_ee_job_clone_data_objects';
    case REWRITE_REFERENCES = 'studio_ee_job_rewrite_element_references';
    case DELETE_DATA_OBJECTS = 'studio_ee_job_delete_data_objects';
    case DELETE_DOCUMENTS = 'studio_ee_job_delete_documents';
}
