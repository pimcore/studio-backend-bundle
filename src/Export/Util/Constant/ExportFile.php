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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum ExportFile: string
{
    use EnumToValueArrayTrait;

    case CSV_FILE_NAME = 'download-csv-{id}.csv';
    case CSV_FOLDER_NAME = 'download-csv-{id}';
    case XLSX_FILE_NAME = 'download-xlsx-{id}.xlsx';
    case XLSX_FOLDER_NAME = 'download-xlsx-{id}';
}
