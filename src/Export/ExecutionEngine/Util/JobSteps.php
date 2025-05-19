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

namespace Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util;

enum JobSteps: string
{
    case DATA_COLLECTION = 'studio_ee_job_step_export_data_collection';
    case CSV_CREATION = 'studio_ee_job_step_csv_creation';
    case XLSX_CREATION = 'studio_ee_job_step_xlsx_creation';
}
