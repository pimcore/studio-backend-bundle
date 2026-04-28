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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Export\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\ExecutionEngineServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final class DownloadServiceTest extends Unit
{

    /**
     * @throws Exception
     */
    public function testDownloadResourceReturnsStreamedResponse(): void
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'test content');
        rewind($stream);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => $stream,
            'fileSize' => 12,
        ]);

        $service = $this->createService(
            storageService: $this->makeEmpty(StorageServiceInterface::class, [
                'getTempStorage' => $storage,
                'tempFileExists' => true,
            ]),
        );

        $response = $service->downloadResourceByJobRunId(
            jobRunId: 1,
            tempFileName: 'export_{id}.zip',
            tempFolderName: 'export_{id}',
            mimeType: 'application/zip',
            downloadName: 'download.zip',
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }


    /**
     * @throws Exception
     */
    public function testCleanupRunsAfterStreamingNotBefore(): void
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'test content');
        rewind($stream);

        $callOrder = [];

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => $stream,
            'fileSize' => 12,
            'delete' => function () use (&$callOrder) {
                $callOrder[] = 'delete';
            },
        ]);

        $storageService = $this->makeEmpty(StorageServiceInterface::class, [
            'getTempStorage' => $storage,
            'tempFileExists' => true,
            'cleanUpFolder' => function () use (&$callOrder) {
                $callOrder[] = 'cleanUpFolder';
            },
        ]);

        $executionEngineService = $this->makeEmpty(ExecutionEngineServiceInterface::class, [
            'validateJobRun' => null,
            'hideJobRun' => function () use (&$callOrder) {
                $callOrder[] = 'hideJobRun';
            },
        ]);

        $service = $this->createService(
            executionEngineService: $executionEngineService,
            storageService: $storageService,
        );

        $response = $service->downloadResourceByJobRunId(
            jobRunId: 1,
            tempFileName: 'export_{id}.zip',
            tempFolderName: 'export_{id}',
            mimeType: 'application/zip',
            downloadName: 'download.zip',
        );

        // Before streaming, no cleanup should have happened
        $this->assertEmpty($callOrder, 'Cleanup must not run before the response is streamed');

        // Now stream the response (simulates what Symfony does on send)
        ob_start();
        $response->sendContent();
        ob_end_clean();

        // After streaming, cleanup should have run in order
        $this->assertSame(
            ['delete', 'cleanUpFolder', 'hideJobRun'],
            $callOrder,
            'Cleanup must run after streaming completes, in the correct order'
        );
    }

    /**
     * @throws Exception
     */
    public function testCleanupFailureIsLoggedNotThrown(): void
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'test content');
        rewind($stream);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => $stream,
            'fileSize' => 12,
            'delete' => function () {
                throw UnableToDeleteFile::atLocation('test', 'Simulated failure');
            },
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $service = $this->createService(
            storageService: $this->makeEmpty(StorageServiceInterface::class, [
                'getTempStorage' => $storage,
                'tempFileExists' => true,
            ]),
            logger: $logger,
        );

        $response = $service->downloadResourceByJobRunId(
            jobRunId: 1,
            tempFileName: 'export_{id}.zip',
            tempFolderName: 'export_{id}',
            mimeType: 'application/zip',
            downloadName: 'download.zip',
        );

        // Streaming should not throw - the error is logged instead
        ob_start();
        $response->sendContent();
        ob_end_clean();
    }

    /**
     * @throws Exception
     */
    public function testThrowsEnvironmentExceptionWhenTempFileNotFound(): void
    {
        $this->expectException(EnvironmentException::class);

        $service = $this->createService(
            storageService: $this->makeEmpty(StorageServiceInterface::class, [
                'getTempStorage' => $this->makeEmpty(FilesystemOperator::class),
                'tempFileExists' => false,
            ]),
        );

        $service->downloadResourceByJobRunId(
            jobRunId: 1,
            tempFileName: 'export_{id}.zip',
            tempFolderName: 'export_{id}',
            mimeType: 'application/zip',
            downloadName: 'download.zip',
        );
    }

    /**
     * @throws Exception
     */
    public function testDownloadResourceSubstitutesJobRunIdInFilePaths(): void
    {
        $actualPath = null;

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => function (string $path) use (&$actualPath) {
                $actualPath = $path;

                $stream = fopen('php://memory', 'rb+');
                fwrite($stream, 'x');
                rewind($stream);

                return $stream;
            },
            'fileSize' => 1,
        ]);

        $service = $this->createService(
            storageService: $this->makeEmpty(StorageServiceInterface::class, [
                'getTempStorage' => $storage,
                'tempFileExists' => true,
            ]),
        );

        $service->downloadResourceByJobRunId(
            jobRunId: 42,
            tempFileName: 'export_{id}.zip',
            tempFolderName: 'folder_{id}',
            mimeType: 'application/zip',
            downloadName: 'download.zip',
        );

        $this->assertSame(
            'folder_42/export_42.zip',
            $actualPath,
            'Job run ID must be substituted into both folder and file name placeholders'
        );
    }

    /**
     * @throws Exception
     */
    public function testDownloadResourceSetsCorrectResponseHeaders(): void
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'file-content');
        rewind($stream);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'readStream' => $stream,
            'fileSize' => 12,
        ]);

        $service = $this->createService(
            storageService: $this->makeEmpty(StorageServiceInterface::class, [
                'getTempStorage' => $storage,
                'tempFileExists' => true,
            ]),
        );

        $response = $service->downloadResourceByJobRunId(
            jobRunId: 1,
            tempFileName: 'export_{id}.zip',
            tempFolderName: 'export_{id}',
            mimeType: 'application/zip',
            downloadName: 'my-export.zip',
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $this->assertSame('12', $response->headers->get('Content-Length'));
        $this->assertSame(
            'attachment; filename="my-export.zip"',
            $response->headers->get('Content-Disposition'),
            'Content-Disposition must use the downloadName parameter, not the internal temp file name'
        );
    }

    /**
     * @throws Exception
     */
    public function testThrowsEnvironmentExceptionWithJobRunIdInMessage(): void
    {
        $service = $this->createService(
            storageService: $this->makeEmpty(StorageServiceInterface::class, [
                'getTempStorage' => $this->makeEmpty(FilesystemOperator::class),
                'tempFileExists' => false,
            ]),
        );

        try {
            $service->downloadResourceByJobRunId(
                jobRunId: 99,
                tempFileName: 'export_{id}.zip',
                tempFolderName: 'export_{id}',
                mimeType: 'application/zip',
                downloadName: 'download.zip',
            );
            $this->fail('Expected EnvironmentException was not thrown');
        } catch (EnvironmentException $e) {
            $this->assertStringContainsString('99', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testDownloadJsonSetsBodyAndHeaders(): void
    {
        $json = '{"users":[1,2,3]}';
        $service = $this->createService();

        $response = $service->downloadJSON($json, 'export-users.json');

        $this->assertSame($json, $response->getContent());
        $this->assertSame(
            MimeTypes::JSON->value,
            $response->headers->get(HttpResponseHeaders::HEADER_CONTENT_TYPE->value)
        );
        $this->assertSame(
            'attachment; filename="export-users.json"',
            $response->headers->get(HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value)
        );
    }

    /**
     * @throws Exception
     */
    public function testDownloadJsonPreservesSpecialCharactersInFilename(): void
    {
        $service = $this->createService();

        $response = $service->downloadJSON('[]', 'file with spaces (1).json');

        $this->assertSame(
            'attachment; filename="file with spaces (1).json"',
            $response->headers->get('Content-Disposition')
        );
    }

    /**
     * @throws Exception
     */
    public function testCleanupDataByJobRunIdException(): void
    {
        $storageService = $this->makeEmpty(StorageServiceInterface::class, [
            'getTempStorage' => $this->makeEmpty(FilesystemOperator::class),
            'tempFileExists' => true,
            'cleanUpFolder' => function () {
                throw UnableToDeleteFile::atLocation('some/path', 'disk full');
            },
        ]);

        $service = $this->createService(storageService: $storageService);

        try {
            $service->cleanupDataByJobRunId(
                jobRunId: 7,
                folderName: 'folder_{id}',
                fileName: 'file_{id}.zip',
            );
            $this->fail('Expected EnvironmentException was not thrown');
        } catch (EnvironmentException $e) {
            $this->assertStringContainsString('7', $e->getMessage());
            $this->assertStringContainsString('Failed to delete file', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testCleanupDataByJobRunId(): void
    {
        $capturedRemoveContents = null;

        $storageService = $this->makeEmpty(StorageServiceInterface::class, [
            'getTempStorage' => $this->makeEmpty(FilesystemOperator::class),
            'tempFileExists' => true,
            'cleanUpFolder' => function (string $folder, bool $removeContents) use (&$capturedRemoveContents) {
                $capturedRemoveContents = $removeContents;
            },
        ]);

        $executionEngineService = $this->makeEmpty(ExecutionEngineServiceInterface::class, [
            'hideJobRun' => Expected::once(),
        ]);

        $service = $this->createService(
            executionEngineService: $executionEngineService,
            storageService: $storageService,
        );

        $service->cleanupDataByJobRunId(
            jobRunId: 1,
            folderName: 'folder_{id}',
            fileName: 'file_{id}.zip',
        );

        $this->assertTrue(
            $capturedRemoveContents,
            'cleanupDataByJobRunId must call cleanUpFolder with removeContents=true'
        );
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?ExecutionEngineServiceInterface $executionEngineService = null,
        ?StorageServiceInterface $storageService = null,
        ?LoggerInterface $logger = null,
    ): DownloadService {
        return new DownloadService(
            $executionEngineService ?? $this->makeEmpty(ExecutionEngineServiceInterface::class),
            $logger ?? $this->makeEmpty(LoggerInterface::class),
            $storageService ?? $this->makeEmpty(StorageServiceInterface::class),
        );
    }
}
