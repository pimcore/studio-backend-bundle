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
        ?string $delimiter = null,
        ?UserInterface $user = null,
    ): void;

    /**
     * @throws FilesystemException
     */
    public function cleanUpFileSystem(int $jobRunId, string $folderName, string $fileName): void;
}
