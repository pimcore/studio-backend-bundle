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

namespace Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util;

enum JobSteps: string
{
    case DATA_COLLECTION = 'studio_ee_job_step_export_data_collection';
    case CSV_CREATION = 'studio_ee_job_step_csv_creation';
    case XLSX_CREATION = 'studio_ee_job_step_xlsx_creation';
}
