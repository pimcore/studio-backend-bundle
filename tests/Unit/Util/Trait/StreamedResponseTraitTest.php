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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Util\Trait;

use Closure;
use Codeception\Test\Unit;
use Exception;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function strlen;

/**
 * @internal
 */
final class StreamedResponseTraitTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetFileStreamedResponseCallsOnStreamCompleteAfterStreaming(): void
    {
        $callbackCalled = false;
        $storage = $this->createStorageWithContent('hello');

        $response = $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'test/file.zip',
            mimeType: 'application/zip',
            filename: 'file.zip',
            storage: $storage,
            onStreamComplete: function () use (&$callbackCalled) {
                $callbackCalled = true;
            },
        );

        $this->assertFalse($callbackCalled, 'Callback must not be called before streaming');

        ob_start();
        $response->sendContent();
        ob_end_clean();

        $this->assertTrue($callbackCalled, 'Callback must be called after streaming completes');
    }

    /**
     * @throws Exception
     */
    public function testGetFileStreamedResponseWorksWithoutCallback(): void
    {
        $storage = $this->createStorageWithContent('data');

        $response = $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'test/file.csv',
            mimeType: 'text/csv',
            filename: 'file.csv',
            storage: $storage,
        );

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame('data', $output);
    }

    /**
     * @throws Exception
     */
    public function testGetFileStreamedResponseError(): void
    {
        $this->expectException(StreamResourceNotFoundException::class);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => function () {
                throw UnableToReadFile::fromLocation('missing', 'disk error');
            },
        ]);

        $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'missing/file.zip',
            mimeType: 'application/zip',
            filename: 'file.zip',
            storage: $storage,
        );
    }

    /**
     * @throws Exception
     */
    public function testResponseHeadersAreCorrectlyConstructed(): void
    {
        $content = 'hello world!';
        $storage = $this->createStorageWithContent($content);

        $response = $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'data/export.csv',
            mimeType: 'text/csv',
            filename: 'quarterly-report.csv',
            storage: $storage,
        );

        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $this->assertSame(
            (string) strlen($content),
            $response->headers->get('Content-Length')
        );
        $this->assertSame(
            'attachment; filename="quarterly-report.csv"',
            $response->headers->get('Content-Disposition'),
            'Default content disposition must be "attachment" with quoted filename'
        );
    }

    /**
     * @throws Exception
     */
    public function testDefaultContentDispositionIsAttachment(): void
    {
        $storage = $this->createStorageWithContent('x');

        $response = $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'test/file.pdf',
            mimeType: 'application/pdf',
            filename: 'document.pdf',
            storage: $storage,
        );

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith(
            HttpResponseHeaders::ATTACHMENT_TYPE->value . ';',
            $disposition
        );
    }

    /**
     * @throws Exception
     */
    public function testCustomContentDispositionIsUsed(): void
    {
        $storage = $this->createStorageWithContent('x');

        $response = $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'test/image.jpg',
            mimeType: 'image/jpeg',
            filename: 'photo.jpg',
            storage: $storage,
            contentDisposition: HttpResponseHeaders::INLINE_TYPE->value,
        );

        $this->assertSame(
            'inline; filename="photo.jpg"',
            $response->headers->get('Content-Disposition')
        );
    }

    /**
     * @throws Exception
     */
    public function testStreamedContentMatchesSourceData(): void
    {
        $binaryContent = str_repeat("\x00\xFF\xAB", 100);
        $storage = $this->createStorageWithContent($binaryContent);

        $response = $this->createTraitHelper()->callGetFileStreamedResponse(
            path: 'test/binary.dat',
            mimeType: 'application/octet-stream',
            filename: 'binary.dat',
            storage: $storage,
        );

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertSame($binaryContent, $output, 'Streamed output must match source data byte-for-byte');
    }

    /**
     * @throws Exception
     */
    private function createStorageWithContent(string $content): FilesystemOperator
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, $content);
        rewind($stream);

        return $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => $stream,
            'fileSize' => strlen($content),
        ]);
    }

    private function createTraitHelper(): object
    {
        return new class {
            use StreamedResponseTrait;

            public function callGetFileStreamedResponse(
                string $path,
                string $mimeType,
                string $filename,
                FilesystemOperator $storage,
                ?Closure $onStreamComplete = null,
                string $contentDisposition = HttpResponseHeaders::ATTACHMENT_TYPE->value,
            ): StreamedResponse {
                return $this->getFileStreamedResponse(
                    $path,
                    $mimeType,
                    $filename,
                    $storage,
                    $contentDisposition,
                    $onStreamComplete,
                );
            }
        };
    }
}
