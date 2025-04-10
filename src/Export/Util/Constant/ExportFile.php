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
