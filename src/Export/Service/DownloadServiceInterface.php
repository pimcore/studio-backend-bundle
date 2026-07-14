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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Symfony\Component\HttpFoundation\Response;
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
        ?string $downloadName = null,
    ): StreamedResponse;

    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException
     */
    public function isResourceAvailableByJobRunId(
        int $jobRunId,
        string $tempFileName,
        string $tempFolderName,
    ): bool;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function cleanupDataByJobRunId(
        int $jobRunId,
        string $folderName,
        string $fileName
    ): void;

    public function downloadJSON(string $json, string $filename): Response;
}
