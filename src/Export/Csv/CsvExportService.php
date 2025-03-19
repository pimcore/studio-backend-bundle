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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Csv;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Export\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;

/**
 * @internal
 */
final readonly class CsvExportService implements ExportServiceInterface
{
    public const string CSV_FILE_NAME = 'download-csv-{id}.csv';

    public const string CSV_FOLDER_NAME = 'download-csv-{id}';

    use TempFilePathTrait;

    public function __construct(
        private StorageServiceInterface $storageService,
        private GridServiceInterface $gridService,
        private string $defaultDelimiter,
    ) {
    }

    /**
     * @throws EnvironmentException|FilesystemException
     */
    public function createExportFile(
        int $id,
        array $columns,
        array $csvData,
        bool $withHeaders = false,
        bool $withGroup = false,
        ?string $delimiter = null,
    ): void {

        $storage = $this->storageService->getTempStorage();

        if ($delimiter === null) {
            $delimiter = $this->defaultDelimiter;
        }

        $data = [];
        if ($withHeaders) {
            $data[]  = $this->getHeaders($columns, $withGroup);
        }

        $data = array_merge($data, $csvData);

        try {
            $csv = Writer::createFromString();
            $csv->setDelimiter($delimiter);
            $csv->insertAll($data);

            $storage->write(
                $this->getCsvFilePath($id, $storage),
                $csv->toString()
            );
        } catch (CannotInsertRecord | Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }
    }

    /**
     * @throws FilesystemException
     */
    public function cleanUpFileSystem(int $jobRunId): void
    {
        $this->storageService->cleanUpFlysystemFile(
            $this->getTempFilePath(
                $jobRunId,
                self::CSV_FOLDER_NAME . '/' . self::CSV_FILE_NAME
            )
        );

        $this->storageService->cleanUpFolder(
            $this->getTempFilePath($jobRunId, self::CSV_FOLDER_NAME)
        );
    }

    private function encodeFunc(?string $value): string
    {
        $value = str_replace('"', '""', $value ?? '');

        //force wrap value in quotes and return
        return '"' . $value . '"';
    }

    private function getHeaders(array $columns, bool $withGroup): array
    {
        if (empty($columns)) {
            return [];
        }

        $columnCollection = $this->gridService->getConfigurationFromArray(
            $columns,
            true
        );

        return $this->gridService->getColumnKeys(
            $columnCollection,
            $withGroup
        );
    }

    /**
     * @throws FilesystemException
     */
    private function getCsvFilePath(int $id, FilesystemOperator $storage): string
    {
        $folderName = $this->getTempFileName($id, self::CSV_FOLDER_NAME);
        $file = $this->getTempFileName($id, self::CSV_FILE_NAME);
        $storage->createDirectory($folderName);

        return $folderName . '/' . $file;
    }
}
