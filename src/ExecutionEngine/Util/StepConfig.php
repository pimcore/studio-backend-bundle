<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum StepConfig: string
{
    use EnumToValueArrayTrait;

    case ID = 'id';
    case CUSTOM_REPORT_CONFIG = 'custom_report_config';
    case CUSTOM_REPORT_TO_EXPORT = 'custom_report_to_export';
    case ASSET_TO_EXPORT = 'asset_to_export';
    case FOLDER_TO_EXPORT = 'folder_to_export';
    case CSV_EXPORT_DATA = 'csv_export_data';
    case CONFIG_CONFIGURATION = 'config';
    case CONFIG_COLUMNS = 'columns';
    case CONFIG_FILTERS = 'filters';
    case SETTINGS_DELIMITER = 'delimiter';
    case SETTINGS_HEADER = 'header';
    case SETTINGS_HEADER_NO_HEADER = 'no_header';
    case SETTINGS_HEADER_TITLE = 'title';
    case SETTINGS_HEADER_NAME = 'name';
    case NEW_LINE = "\r\n";
    case CONFIG_TYPE_ARRAY = 'array';
    case CONFIG_TYPE_INT = 'int';
    case CONFIG_TYPE_STRING = 'string';
    case CONFIG_TYPE_BOOL = 'bool';
}
