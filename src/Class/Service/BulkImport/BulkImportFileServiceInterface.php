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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
interface BulkImportFileServiceInterface
{
    /**
     * @throws ApiInvalidArgumentException
     * @throws EnvironmentException
     */
    public function storeFile(UploadedFile $file): string;

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundException
     * @throws EnvironmentException
     */
    public function readFileData(string $fileId): array;

    /**
     * @throws NotFoundException
     * @throws EnvironmentException
     */
    public function deleteBulkFile(string $fileId): void;

    public function cleanUpFile(string $fileId): void;
}
