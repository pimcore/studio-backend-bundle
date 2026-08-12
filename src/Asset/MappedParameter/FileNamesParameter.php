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
use function sprintf;

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

        if (count($this->fileNames) > self::MAX_FILE_NAMES) {
            throw new InvalidArgumentException(
                sprintf(
                    'fileNames array cannot contain more than %d items, %d given.',
                    self::MAX_FILE_NAMES,
                    count($this->fileNames)
                )
            );
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
