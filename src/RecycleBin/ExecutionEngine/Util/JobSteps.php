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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Util;

/**
 * @internal
 */
enum JobSteps: string
{
    case DELETE_ITEMS = 'studio_ee_job_step_recycle_bin_delete';
    case RESTORE_ITEMS = 'studio_ee_job_step_recycle_bin_restore';
}
