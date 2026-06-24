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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\ExecutionEngine\Util;

/**
 * @internal
 */
enum JobSteps: string
{
    case REASSIGN_OWNER = 'studio_ee_job_step_ownership_management_reassign_owner';
    case DELETE_CONFIGURATIONS = 'studio_ee_job_step_ownership_management_delete';
}
