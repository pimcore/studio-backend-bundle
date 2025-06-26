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

use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Event\PreResponse\LogEntryEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Hydrator\LogHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Repository\LogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class LogService implements LogServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LogHydratorInterface $hydrator,
        private LogRepositoryInterface $logRepository,
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
            $this->logRepository->getTotalCount(),
            $list
        );
    }

    private function getHydratedLogs(array $log): LogEntry
    {
        $entry = $this->hydrator->hydrate($log);
        $this->eventDispatcher->dispatch(new LogEntryEvent($entry), LogEntryEvent::EVENT_NAME);

        return $entry;
    }
}
