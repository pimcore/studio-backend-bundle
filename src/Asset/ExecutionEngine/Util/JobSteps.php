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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util;

enum JobSteps: string
{
    case ZIP_COLLECTION = 'studio_ee_jop_step_zip_collection';
    case ZIP_CREATION = 'studio_ee_jop_step_zip_creation';
    case ASSET_DELETION = 'studio_ee_jop_step_asset_deletion';
    case ASSET_CLONING = 'studio_ee_jop_step_asset_cloning';
}
