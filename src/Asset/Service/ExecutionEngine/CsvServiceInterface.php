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

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;

/**
 * @internal
 */
interface CsvServiceInterface
{
    public const CSV_FILE_NAME = 'download-csv-{id}.csv';
    public const CSV_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . self::CSV_FILE_NAME;

    public function getCsvFile(int $id, Configuration $configuration, array $settings): string;

    public function addData(string $filePath, string $delimiter, array $data): void;

    public function generateCsvFile(ExportAssetParameter $exportAssetParameter): string;

    public function getTempFilePath(int $id, string $path): string;
}
