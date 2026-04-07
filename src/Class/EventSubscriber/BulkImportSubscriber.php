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

namespace Pimcore\Bundle\StudioBackendBundle\Class\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportFileServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class BulkImportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventSubscriberServiceInterface $eventSubscriberService,
        private JobRunRepositoryInterface $jobRunRepository,
        private PublishServiceInterface $publishService,
        private UserTopicServiceInterface $userTopicService,
        private BulkImportFileServiceInterface $bulkImportFileService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class => 'onStateChanged',
        ];
    }

    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if ($event->getJobName() !== Jobs::BULK_IMPORT_CLASS_DEFINITIONS->value) {
            return;
        }

        $state = $event->getNewState();

        match ($state) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                $this->userTopicService->getUserTopic($event->getJobRunOwnerId()),
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

    private function cleanupData(int $jobRunId): void
    {
        $environmentVariables = $this->jobRunRepository->getJobRunById(
            $jobRunId
        )->getJob()?->getEnvironmentData();

        if ($environmentVariables &&
            isset($environmentVariables[EnvironmentVariables::BULK_IMPORT_FILE_ID->value])
        ) {
            $this->bulkImportFileService->cleanUpFile(
                $environmentVariables[EnvironmentVariables::BULK_IMPORT_FILE_ID->value]
            );
        }
    }
}
