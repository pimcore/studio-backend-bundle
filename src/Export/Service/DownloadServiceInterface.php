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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
interface DownloadServiceInterface
{
    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException|StreamResourceNotFoundException
     */
    public function downloadResourceByJobRunId(
        int $jobRunId,
        string $tempFileName,
        string $tempFolderName,
        string $mimeType,
        string $downloadName,
    ): StreamedResponse;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function cleanupDataByJobRunId(
        int $jobRunId,
        string $folderName,
        string $fileName
    ): void;
}
