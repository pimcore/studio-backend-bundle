<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Export\EventSubscriber\Csv;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Export\ExportServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class CsvCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventSubscriberServiceInterface $eventSubscriberService,
        private ExportServiceInterface $csvService
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class  => 'onStateChanged',
        ];
    }

    /**
     * @throws FilesystemException
     * @throws ForbiddenException
     * @throws InvalidArgumentException
     * @throws UserNotFoundException
     * @throws NotFoundException
     * /
     */
    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if ($event->getJobName() !== Jobs::CREATE_CSV->value) {
            return;
        }

        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->eventSubscriberService->handleFinishAndNotify(
                Events::CSV_DOWNLOAD_READY->value,
                $event
            ),
            JobRunStates::FAILED->value => $this->cleanupOnFail($event->getJobRunId()),
            default => null,
        };
    }

    /**
     * @throws FilesystemException
     */
    private function cleanupOnFail(int $jobRunId): void
    {
        $this->csvService->cleanupFileSystem($jobRunId);
    }
}
