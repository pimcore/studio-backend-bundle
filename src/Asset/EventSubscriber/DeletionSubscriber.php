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

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class DeletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PublishServiceInterface $publishService,
        private JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository
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
        if ($event->getJobName() !==  Jobs::DELETE_ASSETS->value) {
            return;
        }

        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                Events::DELETION_FINISHED->value,
                new Finished(
                    $event->getJobRunId(),
                    $event->getJobName(),
                    $event->getNewState()
                )
            ),
            JobRunStates::FINISHED_WITH_ERRORS->value => $this->handleFinishedWithErrors(
                $event->getJobRunId(),
                $event->getJobName()
            ),
            default => null,
        };
    }

    private function handleFinishedWithErrors(
        int $jobRunId,
        string $jobName
    ): void {
        $messages = [];
        $errorLogs = $this->jobRunErrorLogRepository->getLogsByJobRunId($jobRunId);
        foreach ($errorLogs as $errorLog) {
            $messages[] = $errorLog->getErrorMessage();
        }

        $this->publishService->publish(
            Events::FINISHED_WITH_ERRORS->value,
            new Finished(
                $jobRunId,
                $jobName,
                JobRunStates::FINISHED_WITH_ERRORS->value,
                $messages
            )
        );
    }
}
