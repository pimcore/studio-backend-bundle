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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\EventSubscriber;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class CsvCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CsvServiceInterface $csvService,
        private PublishServiceInterface $publishService,
        private StorageServiceInterface $storageService,
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
     */
    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if ($event->getJobName() !== Jobs::CREATE_CSV->value) {
            return;
        }

        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                Events::CSV_DOWNLOAD_READY->value,
                new Finished(
                    $event->getJobRunId(),
                    $event->getJobName(),
                    $event->getJobRunOwnerId(),
                    $event->getNewState()
                )
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
        $this->storageService->cleanUpFlysystemFile(
            $this->csvService->getTempFilePath(
                $jobRunId,
                CsvServiceInterface::CSV_FOLDER_NAME . '/' . CsvServiceInterface::CSV_FILE_NAME
            )
        );

        $this->storageService->cleanUpFolder(
            $this->csvService->getTempFilePath($jobRunId, CsvServiceInterface::CSV_FOLDER_NAME)
        );
    }
}
