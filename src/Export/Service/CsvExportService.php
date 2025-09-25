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

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Override;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFile;

/**
 * @internal
 */
final readonly class CsvExportService extends AbstractExportService
{
    /**
     * @throws EnvironmentException|FilesystemException
     */
    #[Override]
    protected function generateExportFile(
        int $id,
        FilesystemOperator $storage,
        array $headers,
        array $exportData,
        string $delimiter
    ): void {

        $data = [];

        if (!empty($headers)) {
            $data[] = $headers;
        }

        $data = array_merge($data, $exportData);

        try {
            $csv = Writer::createFromString();
            $csv->setDelimiter($delimiter);
            $csv->insertAll($data);

            $storage->write(
                $this->getExportFilePath(
                    $id,
                    $storage,
                    ExportFile::CSV_FILE_NAME->value,
                    ExportFile::CSV_FOLDER_NAME->value
                ),
                $csv->toString()
            );
        } catch (CannotInsertRecord | Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }
    }
}
