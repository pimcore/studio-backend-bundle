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

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util;

enum JobSteps: string
{
    case ELEMENT_PATCHING = 'studio_ee_job_step_element_patching';
    case ELEMENT_FOLDER_PATCHING = 'studio_ee_job_step_folder_patching';
    case ELEMENT_REWRITE_REFERENCE = 'studio_ee_job_step_element_rewrite_reference';
    case ELEMENT_RECYCLING = 'studio_ee_job_step_element_recycling';
    case ELEMENT_DELETION = 'studio_ee_job_step_element_deletion';
    case ELEMENT_BATCH_TAG_ASSIGN = 'studio_ee_job_step_batch_tag_assign';
    case ELEMENT_BATCH_TAG_REPLACE = 'studio_ee_job_step_batch_tag_replace';
}
