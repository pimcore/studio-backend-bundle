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
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Model\GridExportData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\TempFilePathTrait;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
abstract readonly class AbstractExportService implements ExportServiceInterface
{
    use TempFilePathTrait;

    public function __construct(
        private ColumnConfigurationServiceInterface $columnConfigurationService,
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
        GridExportData $gridExportData,
        UserInterface $user,
        ?string $delimiter = null,
    ): void {
        $storage = $this->storageService->getTempStorage();

        if ($delimiter === null) {
            $delimiter = $this->defaultDelimiter;
        }

        $headers = [];
        if ($gridExportData->isWithHeaders()) {
            $columnsDefinitions = $this->getColumnConfigurations($gridExportData->getExportDataInfo(), $user);
            $headers = $this->getHeaders(
                $gridExportData->getColumns(),
                $columnsDefinitions,
                $gridExportData->isWithGroup()
            );
        }

        $this->generateExportFile($id, $storage, $headers, $gridExportData->getExportData(), $delimiter);
    }

    /**
     * @throws FilesystemException
     */
    public function cleanUpFileSystem(int $jobRunId, string $folderName, string $fileName): void
    {
        $this->storageService->cleanUpFlysystemFile(
            $this->getTempFilePath(
                $jobRunId,
                $folderName . '/' . $fileName
            )
        );

        $this->storageService->cleanUpFolder(
            $this->getTempFilePath($jobRunId, $folderName)
        );
    }

    abstract protected function generateExportFile(
        int $id,
        FilesystemOperator $storage,
        array $headers,
        array $exportData,
        string $delimiter
    ): void;

    /**
     * @throws FilesystemException
     */
    protected function getExportFilePath(
        int $id,
        FilesystemOperator $storage,
        string $fileName,
        string $folderName
    ): string {
        $folderName = $this->getTempFileName($id, $folderName);
        $file = $this->getTempFileName($id, $fileName);
        $storage->createDirectory($folderName);

        return $folderName . '/' . $file;
    }

    protected function getHeaders(array $columns, array $columnsDefinitions, bool $withGroup): array
    {
        if (empty($columns)) {
            return [];
        }

        $columnCollection = $this->gridService->getConfigurationForExport(
            $columns,
            $columnsDefinitions
        );

        return $this->gridService->getColumnKeys(
            $columnCollection,
            $withGroup
        );
    }

    protected function getColumnConfigurations(array $csvExportDataInfo, UserInterface $user): array
    {
        return match($csvExportDataInfo['type']) {
            ElementTypes::TYPE_OBJECT => $this->getDataObjectColumnConfigurations(
                $csvExportDataInfo['classId'],
                $user
            ),
            ElementTypes::TYPE_ASSET => $this->getAssetColumnConfigurations(),
            default => throw new EnvironmentException('Invalid type'),
        };
    }

    private function getDataObjectColumnConfigurations(string $classId, UserInterface $user): array
    {
        return $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classId,
            1,
            $user
        );
    }

    private function getAssetColumnConfigurations(): array
    {
        return $this->columnConfigurationService->getAvailableAssetColumnConfiguration();
    }
}
