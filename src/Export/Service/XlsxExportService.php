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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Override;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFile;

/**
 * @internal
 */
final readonly class XlsxExportService extends AbstractExportService
{
    /**
     * @throws FilesystemException
     */
    #[Override]
    protected function generateExportFile(
        array $data,
        int $id,
        FilesystemOperator $storage,
        $delimiter
    ): void {
        $csvReader = new Csv();
        $csvReader->setDelimiter($delimiter);
        $csvReader->setSheetIndex(0);

        $spreadsheet = $csvReader->loadSpreadsheetFromString(implode("\n", ['id', 83]));
        $writer = new Xlsx($spreadsheet);
        $stream = fopen('php://temp', 'rb+');
        $writer->save($stream);
        rewind($stream);

        $storage->writeStream(
            $this->getExportFilePath(
                $id,
                $storage,
                ExportFile::XLSX_FILE_NAME->value,
                ExportFile::XLSX_FOLDER_NAME->value
            ),
            $stream
        );
    }
}
