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
use Pimcore\Bundle\StudioBackendBundle\Export\Model\GridExportData;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ExportServiceInterface
{
    public function createExportFile(
        int $id,
        GridExportData $gridExportData,
        UserInterface $user,
        ?string $delimiter = null,
    ): void;

    /**
     * @throws FilesystemException
     */
    public function cleanUpFileSystem(int $jobRunId, string $folderName, string $fileName): void;
}
