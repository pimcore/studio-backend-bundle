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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function count;
use function is_string;
use function sprintf;
use function str_contains;

/**
 * Validates the shape of the body. Resolving names to asset keys needs the
 * element service and happens in UploadInfoService.
 *
 * @internal
 */
final readonly class FileNamesParameter
{
    /**
     * Upper bound for a single existence check request. Each file name is resolved
     * individually, so an unbounded list would hold a worker for an arbitrary time.
     */
    public const int MAX_FILE_NAMES = 100;

    /**
     * @var array<string>
     */
    private array $fileNames;

    /**
     * @param array<string> $fileNames
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $fileNames)
    {
        if (empty($fileNames)) {
            throw new InvalidArgumentException('fileNames array cannot be empty.');
        }

        $fileNamesCount = count($fileNames);
        if ($fileNamesCount > self::MAX_FILE_NAMES) {
            throw new InvalidArgumentException(
                sprintf(
                    'fileNames array cannot contain more than %d items, %d given.',
                    self::MAX_FILE_NAMES,
                    $fileNamesCount
                )
            );
        }

        foreach ($fileNames as $fileName) {
            if (!is_string($fileName) || $fileName === '') {
                throw new InvalidArgumentException('Each fileNames item must be a non-empty string.');
            }

            if (str_contains($fileName, '/') || str_contains($fileName, '\\')) {
                throw new InvalidArgumentException('Each fileNames item must be a single asset key.');
            }
        }

        $this->fileNames = $fileNames;
    }

    /**
     * @return array<string>
     */
    public function getFileNames(): array
    {
        return $this->fileNames;
    }
}
