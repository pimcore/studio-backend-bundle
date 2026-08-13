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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\Service as ElementService;
use function count;
use function is_string;
use function sprintf;
use function str_contains;

/**
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
     * @param array<string> $fileNames
     */
    public function __construct(
        private array $fileNames
    ) {
        if (empty($this->fileNames)) {
            throw new InvalidArgumentException('fileNames array cannot be empty.');
        }

        $fileNamesCount = count($this->fileNames);
        if ($fileNamesCount > self::MAX_FILE_NAMES) {
            throw new InvalidArgumentException(
                sprintf(
                    'fileNames array cannot contain more than %d items, %d given.',
                    self::MAX_FILE_NAMES,
                    $fileNamesCount
                )
            );
        }

        foreach ($this->fileNames as $index => $fileName) {
            if (!is_string($fileName) || $fileName === '') {
                throw new InvalidArgumentException('Each fileNames item must be a non-empty string.');
            }

            if (str_contains($fileName, '/') || str_contains($fileName, '\\')) {
                throw new InvalidArgumentException('Each fileNames item must be a single asset key.');
            }

            $validFileName = ElementService::getValidKey($fileName, ElementTypes::TYPE_ASSET);
            if ($validFileName === '') {
                throw new InvalidArgumentException('Each fileNames item must be a valid asset key.');
            }

            $this->fileNames[$index] = $validFileName;
        }
    }

    /**
     * @return array<string>
     */
    public function getFileNames(): array
    {
        return $this->fileNames;
    }
}
