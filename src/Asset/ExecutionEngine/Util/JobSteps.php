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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util;

enum JobSteps: string
{
    case ZIP_CREATION = 'studio_ee_job_step_zip_creation';
    case ZIP_COPY = 'studio_ee_job_step_zip_copy';
    case ZIP_UPLOADING = 'studio_ee_jop_step_zip_uploading';
    case ASSET_CLONING = 'studio_ee_job_step_asset_cloning';
    case ASSET_UPLOADING = 'studio_ee_job_step_asset_uploading';
}
