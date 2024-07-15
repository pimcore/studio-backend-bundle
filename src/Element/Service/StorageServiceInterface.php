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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;

/**
 * @internal
 */
interface StorageServiceInterface
{
    /**
     * @throws EnvironmentException
     */
    public function removeTempFile(string $location): void;

    /**
     * @throws EnvironmentException
     */
    public function tempFileExists(string $location): bool;

    /**
     * @throws FilesystemException
     */
    public function cleanUpFolder(
        string $folder
    ): void;

    public function cleanUpLocalFile(
        string $archivePath
    ): void;

    public function cleanUpFlysystemFile(
        string $archivePath
    ): void;

    public function getThumbnailStorage(): FilesystemOperator;

    public function getTempStorage(): FilesystemOperator;
}
