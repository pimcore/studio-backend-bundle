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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateAssetFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;

/**
 * @internal
 */
interface CsvServiceInterface
{
    public const CSV_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/download-csv-{id}.zip';


    public function getCsvFile(int $id): ?FilesystemOperator;

    public function addAsset(): void;

    public function generateCsvFile(ExportAssetParameter $exportAssetParameter): string;
    public function getTempFilePath(int $id, string $path): string;
}