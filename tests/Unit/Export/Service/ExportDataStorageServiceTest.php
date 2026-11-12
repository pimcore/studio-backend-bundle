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

use Codeception\Test\Unit;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToDeleteDirectory;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @internal
 */
final class ExportDataStorageServiceTest extends Unit
{
    private string $directory;

    private Filesystem $storage;

    protected function _before(): void
    {
        $this->directory = sys_get_temp_dir() . '/export-data-storage-test-' . uniqid('', true);
        $this->storage = new Filesystem(new LocalFilesystemAdapter($this->directory));
    }

    protected function _after(): void
    {
        (new SymfonyFilesystem())->remove($this->directory);
    }

    public function testLoadReturnsNullIfNothingWasStored(): void
    {
        $this->assertNull($this->createService()->load(1));
    }

    public function testLoadReturnsTheRowsOfAllBatchesInBatchOrder(): void
    {
        $service = $this->createService();
        // Stored out of order and with a batch number that is longer than the one before it
        $service->store(1, 10, [['row-10-a'], ['row-10-b']]);
        $service->store(1, 2, [['row-2-a']]);
        $service->store(1, 0, [['row-0-a'], ['row-0-b']]);

        $this->assertSame(
            [['row-0-a'], ['row-0-b'], ['row-2-a'], ['row-10-a'], ['row-10-b']],
            $service->load(1)
        );
    }

    public function testStoreKeepsTheRowsOfTheBatchesOfOtherJobRunsApart(): void
    {
        $service = $this->createService();
        $service->store(1, 0, [['one']]);
        $service->store(2, 0, [['two']]);

        $this->assertSame([['one']], $service->load(1));
        $this->assertSame([['two']], $service->load(2));
    }

    public function testStoringABatchAgainReplacesIt(): void
    {
        $service = $this->createService();
        $service->store(1, 0, [['first']]);
        // A retried step collects the same elements again
        $service->store(1, 0, [['first']]);

        $this->assertSame([['first']], $service->load(1));
    }

    public function testRowsKeepTheirValueTypes(): void
    {
        $rows = [
            [1, 1.0, 'text', true, null, ['nested' => ['value' => 2.5]]],
            ['key' => 'value'],
        ];
        $service = $this->createService();
        $service->store(1, 0, $rows);

        $this->assertSame($rows, $service->load(1));
    }

    public function testRowsAreReturnedAsListWhenTheyWereKeyedByElementId(): void
    {
        $service = $this->createService();
        $service->store(1, 0, [100 => ['a'], 101 => ['b']]);
        $service->store(1, 1, [102 => ['c']]);

        $this->assertSame([['a'], ['b'], ['c']], $service->load(1));
    }

    public function testCleanUpRemovesAllBatches(): void
    {
        $service = $this->createService();
        $service->store(1, 0, [['one']]);
        $service->store(1, 1, [['two']]);
        $service->store(2, 0, [['other job run']]);

        $service->cleanUp(1);

        $this->assertNull($service->load(1));
        $this->assertSame([['other job run']], $service->load(2));
    }

    public function testCleanUpIsAllowedWithoutStoredData(): void
    {
        $this->createService()->cleanUp(1);

        $this->expectNotToPerformAssertions();
    }

    public function testCleanUpDoesNotThrowButLogsIfTheStorageFails(): void
    {
        $storageService = $this->createMock(StorageServiceInterface::class);
        $storageService->method('cleanUpFolder')->willThrowException(
            UnableToDeleteDirectory::atLocation('export-data-1')
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        (new ExportDataStorageService($storageService, $logger))->cleanUp(1);
    }

    public function testLoadFailsWithAnEnvironmentExceptionForUnreadableData(): void
    {
        $service = $this->createService();
        $service->store(1, 0, [['one']]);
        $this->storage->write('export-data-1/batch-00000001.json', '{not json');

        $this->expectException(EnvironmentException::class);

        $service->load(1);
    }

    private function createService(): ExportDataStorageService
    {
        $storageService = $this->createMock(StorageServiceInterface::class);
        $storageService->method('getTempStorage')->willReturn($this->storage);
        $storageService->method('cleanUpFolder')->willReturnCallback(
            fn (string $folder) => $this->storage->deleteDirectory($folder)
        );

        return new ExportDataStorageService($storageService, $this->createMock(LoggerInterface::class));
    }
}
