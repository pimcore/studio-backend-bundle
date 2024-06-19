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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service;

use Pimcore\Model\Asset;
use ZipArchive;

/**
 * @internal
 */
final class ZipService implements ZipServiceInterface
{
    private const ZIP_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/download-zip-{id}.zip';

    private const ZIP_ID_PLACEHOLDER = '{id}';

    public function getZipArchive(int $id): ?ZipArchive
    {
        $zip = $this->getTempZipFilePath($id);

        $archive = new ZipArchive();

        $state = false;

        if (is_file($zip)) {
            $state = $archive->open($zip);
        }

        if (!$state) {
            $state = $archive->open($zip, ZipArchive::CREATE);
        }

        if (!$state) {
            return null;
        }

        return $archive;
    }

    public function getTempZipFilePath(int $id): string
    {
        return str_replace(self::ZIP_ID_PLACEHOLDER, (string)$id, self::ZIP_FILE_PATH);
    }

    public function addFile(ZipArchive $archive, Asset $asset): void
    {
        $archive->addFile(
            $asset->getLocalFile(),
            preg_replace(
                '@^' . preg_quote($asset->getRealPath(), '@') . '@i',
                '',
                $asset->getRealFullPath()
            )
        );
    }
}
