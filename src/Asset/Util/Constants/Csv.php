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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants;

use Pimcore\Bundle\StudioBackendBundle\Util\Traits\EnumToValueArrayTrait;

enum Csv: string
{
    use EnumToValueArrayTrait;

    case ASSET_TO_EXPORT = 'asset_to_export';
    case ASSET_EXPORT_DATA = 'asset_export_data';
    case JOB_STEP_CONFIG_SETTINGS = 'settings';
    case JOB_STEP_CONFIG_CONFIGURATION = 'configuration';
    case SETTINGS_DELIMITER = 'delimiter';
    case SETTINGS_HEADER = 'header';
    case SETTINGS_HEADER_NO_HEADER = 'no_header';
    case SETTINGS_HEADER_TITLE = 'title';
    case SETTINGS_HEADER_NAME = 'name';
    case NEW_LINE = "\r\n";
}
