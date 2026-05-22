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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\ApplicationLogger\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Event\PreResponse\LogEntryEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Hydrator\LogHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Repository\LogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Service\LogService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final class LogServiceTest extends Unit
{
    public function testStreamFileObjectNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'fileExists' => false,
        ]);

        $service = $this->createService(
            storageResolver: $this->makeEmpty(StorageResolverInterface::class, [
                'get' => $storage,
            ])
        );

        $service->streamFileObject('/non-existent/file.log');
    }

    public function testStreamFileObjectFilesystemError(): void
    {
        $this->expectException(StreamResourceNotFoundException::class);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'fileExists' => function () {
                throw UnableToReadFile::fromLocation('/broken/file.log', 'disk error');
            },
        ]);

        $service = $this->createService(
            storageResolver: $this->makeEmpty(StorageResolverInterface::class, [
                'get' => $storage,
            ])
        );

        $service->streamFileObject('/broken/file.log');
    }

    public function testStreamFileObjectSuccess(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'log content');
        rewind($stream);

        $storage = $this->makeEmpty(FilesystemOperator::class, [
            'fileExists' => true,
            'readStream' => $stream,
        ]);

        $service = $this->createService(
            storageResolver: $this->makeEmpty(StorageResolverInterface::class, [
                'get' => $storage,
            ])
        );

        $response = $service->streamFileObject('/existing/file.log');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(HttpResponseCodes::SUCCESS->value, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
    }

    public function testListEntriesDispatchesEvents(): void
    {
        $logData = [
            ['id' => 1, 'message' => 'test'],
            ['id' => 2, 'message' => 'test2'],
        ];

        $logEntry = new LogEntry(id: 1, priority: 1);

        $service = $this->createService(
            eventDispatcher: $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => Expected::exactly(2, new LogEntryEvent($logEntry)),
            ]),
            hydrator: $this->makeEmpty(LogHydratorInterface::class, [
                'hydrate' => $logEntry,
            ]),
            logRepository: $this->makeEmpty(LogRepositoryInterface::class, [
                'list' => $logData,
                'getTotalCount' => 2,
            ])
        );

        $result = $service->listLogEntries(new CollectionFilterParameter());

        $this->assertSame(2, $result->getTotalItems());
        $this->assertCount(2, $result->getItems());
    }

    public function testListEntriesEmpty(): void
    {
        $service = $this->createService(
            logRepository: $this->makeEmpty(LogRepositoryInterface::class, [
                'list' => [],
                'getTotalCount' => 0,
            ])
        );

        $result = $service->listLogEntries(new CollectionFilterParameter());

        $this->assertSame(0, $result->getTotalItems());
        $this->assertCount(0, $result->getItems());
    }

    private function createService(
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LogHydratorInterface $hydrator = null,
        ?LogRepositoryInterface $logRepository = null,
        ?StorageResolverInterface $storageResolver = null,
    ): LogService {
        return new LogService(
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $hydrator ?? $this->makeEmpty(LogHydratorInterface::class),
            $logRepository ?? $this->makeEmpty(LogRepositoryInterface::class),
            $storageResolver ?? $this->makeEmpty(StorageResolverInterface::class),
        );
    }
}
