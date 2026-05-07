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

namespace Pimcore\Bundle\StudioBackendBundle\Export\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function in_array;

/**
 * @internal
 */
final readonly class FolderCollectionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JobRunRepositoryInterface $jobRunRepository,
        private PublishServiceInterface $publishService,
        private UserTopicServiceInterface $userTopicService,
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class  => 'onStateChanged',
        ];
    }

    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if (!in_array($event->getJobName(), [
            Jobs::COLLECT_CSV_FOLDER_EXPORT_ELEMENTS->value,
            Jobs::COLLECT_XLSX_FOLDER_EXPORT_ELEMENTS->value,
        ], true)) {
            return;
        }

        $jobRun = $this->jobRunRepository->getJobRunById($event->getJobRunId());
        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                $this->userTopicService->getUserTopic($event->getJobRunOwnerId()),
                new Finished(
                    $event->getJobRunId(),
                    $event->getJobName(),
                    $event->getJobRunOwnerId(),
                    $event->getNewState(),
                    [JobRunContext::CHILD_JOB_RUN->value =>
                        $jobRun->getContext()[JobRunContext::CHILD_JOB_RUN->value] ?? null,
                    ]
                )
            ),
            default => null,
        };
    }
}
