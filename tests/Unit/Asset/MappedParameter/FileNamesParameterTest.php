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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\FileNamesParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_fill;

/**
 * @internal
 */
final class FileNamesParameterTest extends Unit
{
    public function testThrowsInvalidArgumentExceptionWhenFileNamesEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileNamesParameter([]);
    }

    public function testThrowsInvalidArgumentExceptionWhenFileNamesExceedMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileNamesParameter(array_fill(0, FileNamesParameter::MAX_FILE_NAMES + 1, 'file.jpg'));
    }

    public function testAcceptsExactlyTheMaximumAmountOfFileNames(): void
    {
        $parameter = new FileNamesParameter(array_fill(0, FileNamesParameter::MAX_FILE_NAMES, 'file.jpg'));

        $this->assertCount(FileNamesParameter::MAX_FILE_NAMES, $parameter->getFileNames());
    }

    public function testGetFileNamesKeepsTheGivenOrder(): void
    {
        $fileNames = ['b.jpg', 'a.jpg', 'c.jpg'];

        $this->assertSame($fileNames, (new FileNamesParameter($fileNames))->getFileNames());
    }

    public function testThrowsInvalidArgumentExceptionWhenFileNameIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileNamesParameter(['file.jpg', 1]);
    }

    public function testThrowsInvalidArgumentExceptionWhenFileNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileNamesParameter(['file.jpg', '']);
    }

    public function testThrowsInvalidArgumentExceptionWhenFileNameContainsPathSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileNamesParameter(['file.jpg', 'subfolder/another.jpg']);
    }

    public function testKeepsEntriesAsGiven(): void
    {
        $this->assertSame(
            ['my test.jpg'],
            (new FileNamesParameter(['my test.jpg']))->getFileNames()
        );
    }
}
