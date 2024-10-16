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

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util;

enum JobSteps: string
{
    case ELEMENT_PATCHING = 'studio_ee_job_step_element_patching';
    case ELEMENT_FOLDER_PATCHING = 'studio_ee_job_step_folder_patching';
    case ELEMENT_REWRITE_REFERENCE = 'studio_ee_job_step_element_rewrite_reference';
    case ELEMENT_OBJECT_DELETION = 'studio_ee_job_step_element_deletion';
    case ELEMENT_RECYCLING = 'studio_ee_job_step_element_recycling';
    case ELEMENT_DELETION = 'studio_ee_job_step_asset_deletion';
}
