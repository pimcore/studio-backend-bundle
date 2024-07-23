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
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class UploadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventSubscriberServiceInterface $eventSubscriberService,
        private JobRunRepositoryInterface $jobRunRepository,
        private PublishServiceInterface $publishService,
        private StorageServiceInterface $storageService
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
        if ($event->getJobName() !== Jobs::UPLOAD_ASSETS->value) {
            return;
        }
        $state = $event->getNewState();

        match ($state) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                Events::ASSET_UPLOAD_FINISHED->value,
                new Finished(
                    $event->getJobRunId(),
                    $event->getJobName(),
                    $event->getJobRunOwnerId(),
                    $event->getNewState()
                )
            ),
            JobRunStates::FINISHED_WITH_ERRORS->value => $this->eventSubscriberService->handleFinishedWithErrors(
                $event->getJobRunId(),
                $event->getJobRunOwnerId(),
                $event->getJobName()
            ),
            default => null,
        };

        if ($state !== JobRunStates::RUNNING->value && $state !== JobRunStates::NOT_STARTED->value) {
            $this->cleanupData($event->getJobRunId());
        }
    }

    /**
     * @throws FilesystemException
     */
    private function cleanupData(int $jobRunId): void
    {
        $environmentVariables = $this->jobRunRepository->getJobRunById(
            $jobRunId
        )->getJob()?->getEnvironmentData();
        if ($environmentVariables && isset($environmentVariables[EnvironmentVariables::UPLOAD_FOLDER_NAME->value])) {
            $this->storageService->getTempStorage()->deleteDirectory(
                $environmentVariables[EnvironmentVariables::UPLOAD_FOLDER_NAME->value]
            );
        }
    }
}
