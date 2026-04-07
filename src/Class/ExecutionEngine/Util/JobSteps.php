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

namespace Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\Util;

enum JobSteps: string
{
    case FIELD_COLLECTION_IMPORTING = 'studio_ee_job_step_field_collection_importing';
    case CLASS_IMPORTING = 'studio_ee_job_step_class_importing';
    case CUSTOM_LAYOUT_IMPORTING = 'studio_ee_job_step_custom_layout_importing';
    case OBJECT_BRICK_IMPORTING = 'studio_ee_job_step_object_brick_importing';
    case BULK_IMPORT_CLEANUP = 'studio_ee_job_step_bulk_import_cleanup';
}
