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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Service;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\ApplicationLoggerBundle\Enum\LogLevel;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\StorageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Event\PreResponse\LogEntryEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Hydrator\LogHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Repository\LogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function sprintf;

/**
 * @internal
 */
final readonly class LogService implements LogServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LogHydratorInterface $hydrator,
        private LogRepositoryInterface $logRepository,
        private StorageResolverInterface $storageResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listLogEntries(CollectionFilterParameter $parameters): Collection
    {
        $logs = $this->logRepository->list($parameters);
        $list = [];

        foreach ($logs as $log) {
            $list[] = $this->getHydratedLogs($log);
        }

        return new Collection(
            $this->logRepository->getTotalCount($parameters),
            $list
        );
    }

    /**
     * {@inheritdoc}
     */
    public function listComponents(): array
    {
        return $this->logRepository->getComponents();
    }

    public function listPriorities(): array
    {
        return array_column(LogLevel::cases(), 'value');
    }

    public function streamFileObject(string $filePath): StreamedResponse
    {
        $storage = $this->storageResolver->get('application_log');

        try {
            if (!$storage->fileExists($filePath)) {
                throw new NotFoundException('File object', $filePath, 'filePath');
            }

            $stream = $storage->readStream($filePath);
        } catch (FilesystemException $e) {
            throw new StreamResourceNotFoundException(
                sprintf('Could not read file object at path "%s": %s', $filePath, $e->getMessage())
            );
        }

        return new StreamedResponse(
            static function () use ($stream) {
                echo stream_get_contents($stream);
            },
            HttpResponseCodes::SUCCESS->value,
            [HttpResponseHeaders::HEADER_CONTENT_TYPE->value => MimeTypes::PLAIN_TEXT->value]
        );
    }

    private function getHydratedLogs(array $log): LogEntry
    {
        $entry = $this->hydrator->hydrate($log);
        $this->eventDispatcher->dispatch(new LogEntryEvent($entry), LogEntryEvent::EVENT_NAME);

        return $entry;
    }
}
