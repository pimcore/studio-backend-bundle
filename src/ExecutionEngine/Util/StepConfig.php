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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum StepConfig: string
{
    use EnumToValueArrayTrait;

    case ID = 'id';
    case CUSTOM_REPORT_CONFIG = 'custom_report_config';
    case CUSTOM_REPORT_TO_EXPORT = 'custom_report_to_export';
    case ELEMENT_CLASS_ID = 'element_class_id';
    case ELEMENTS_TO_EXPORT = 'elements_to_export';
    case ELEMENT_TYPE = 'element_type';
    case EXPORT_FORMAT = 'export_format';
    case FOLDER_TO_EXPORT = 'folder_to_export';
    case GRID_EXPORT_DATA = 'grid_export_data';
    case GRID_EXPORT_DATA_INFO = 'grid_export_data_info';
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
    case ITEMS_TO_BATCH_DELETE = 'items_to_batch_delete';
    case ITEMS_TO_DELETE = 'items_to_delete';
    case ELEMENT_TYPE_TO_BATCH_DELETE = 'element_type_to_batch_delete';
}
