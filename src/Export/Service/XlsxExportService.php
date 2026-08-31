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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Override;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
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
        int $id,
        FilesystemOperator $storage,
        array $headers,
        array $exportData,
        string $delimiter,
        ?string $sheetName = null,
    ): void {
        $csvReader = new Csv();
        $csvReader->setDelimiter($delimiter);
        $csvReader->setSheetIndex(0);

        $spreadsheet = $csvReader->loadSpreadsheetFromString($this->processData($delimiter, $headers, $exportData));

        if ($sheetName !== null && $sheetName !== '') {
            $spreadsheet->getActiveSheet()->setTitle($sheetName);
        }

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

    private function processData(string $delimiter, array $headers, array $exportData): string
    {
        $data[] = implode($delimiter, $headers) . StepConfig::NEW_LINE->value;
        foreach ($exportData as $row) {
            $data[] = implode($delimiter, array_map([$this, 'encodeFunc'], $row)) . StepConfig::NEW_LINE->value;
        }

        return implode($data);
    }

    private function encodeFunc(?string $value): string
    {
        $value = str_replace('"', '""', $value ?? '');

        //force wrap value in quotes and return
        return '"' . $value . '"';
    }
}
