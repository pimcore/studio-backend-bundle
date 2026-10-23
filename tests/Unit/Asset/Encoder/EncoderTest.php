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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Encoder;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\MaxFileSizeExceededException;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Text;

/**
 * @internal
 */
final class EncoderTest extends Unit
{
    private const MAX_FILE_SIZE = 3000000;

    private TextEncoderInterface $encoder;

    public function _before(): void
    {
        $this->encoder = new TextEncoder(self::MAX_FILE_SIZE);
    }

    public function testWrongElementType(): void
    {
        $element = new Document();

        $this->expectException(InvalidElementTypeException::class);

        $this->encoder->encodeUTF8($element);
    }

    /**
     * @throws Exception
     */
    public function testFileSizeExceeded(): void
    {
        $element = $this->makeEmpty(Text::class, ['getFileSize' => self::MAX_FILE_SIZE + 1]);

        $this->expectException(MaxFileSizeExceededException::class);

        $this->encoder->encodeUTF8($element);
    }

    /**
     * @throws Exception
     */
    public function testFileSizeAboveDefaultButWithinConfiguredLimit(): void
    {
        $element = $this->makeEmpty(Text::class, ['getFileSize' => 2500000, 'getData' => 'abc']);

        $this->assertSame('abc', $this->encoder->encodeUTF8($element));
    }

    /**
     * @throws Exception
     */
    public function testFileSizeAtConfiguredLimit(): void
    {
        $element = $this->makeEmpty(Text::class, ['getFileSize' => self::MAX_FILE_SIZE, 'getData' => 'abc']);

        $this->assertSame('abc', $this->encoder->encodeUTF8($element));
    }

    /**
     * @throws Exception
     */
    public function testUTF8Encoding(): void
    {
        $element = $this->makeEmpty(Text::class, ['getData' => 'Héllö, 世界!']);
        $encodedData = $this->encoder->encodeUTF8($element);

        $this->assertTrue(mb_check_encoding($encodedData, 'UTF-8'));
    }
}
