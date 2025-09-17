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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\MaxFileSizeExceededException;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Text;

/**
 * @internal
 */
#[CoversClass(TextEncoder::class)]
#[UsesClass(MaxFileSizeExceededException::class)]
#[UsesClass(InvalidElementTypeException::class)]
#[UsesClass(AbstractApiException::class)]
final class EncoderTest extends TestCase
{
    private TextEncoderInterface $encoder;

    protected function setUp(): void
    {
        $this->encoder = new TextEncoder();
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
        $element = $this->createMock(Text::class);
        $element->method('getFileSize')->willReturn(2000001);

        $this->expectException(MaxFileSizeExceededException::class);

        $this->encoder->encodeUTF8($element);
    }

    /**
     * @throws Exception
     */
    public function testUTF8Encoding(): void
    {
        $element = $this->createMock(Text::class);
        $element->method('getData')->willReturn('Héllö, 世界!');

        $encodedData = $this->encoder->encodeUTF8($element);

        $this->assertTrue(mb_check_encoding($encodedData, 'UTF-8'));
    }
}
