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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Command;

use function strlen;
use function substr;

/**
 * A destination that takes only part of what it is given, which is what `fwrite()` does on a
 * pipe, a socket, or a volume that is running out of space - a count smaller than the string,
 * including zero, and no failure reported.
 *
 * @internal
 */
final class ShortWriteStream
{
    public static int $acceptPerWrite = 4;

    /**
     * How many writes are taken before the destination stops taking anything, or null for a
     * destination that never fills up.
     */
    public static ?int $stopAfterWrites = null;

    public static string $written = '';

    public static int $writes = 0;

    /**
     * @var resource|null
     */
    public $context;

    public static function reset(): void
    {
        self::$acceptPerWrite = 4;
        self::$stopAfterWrites = null;
        self::$written = '';
        self::$writes = 0;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        if (self::$stopAfterWrites !== null && self::$writes >= self::$stopAfterWrites) {
            return 0;
        }

        self::$writes++;
        $taken = substr($data, 0, self::$acceptPerWrite);
        self::$written .= $taken;

        return strlen($taken);
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_close(): void
    {
    }

    public function stream_eof(): bool
    {
        return true;
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }
}
