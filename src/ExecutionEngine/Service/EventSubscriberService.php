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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\SendNotificationParameters;
use Pimcore\Bundle\StudioBackendBundle\Notification\Service\SendNotificationServiceInterface;

/**
 * @internal
 */
final readonly class EventSubscriberService implements EventSubscriberServiceInterface
{
    public function __construct(
        private JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private PublishServiceInterface $publishService,
        private UserTopicServiceInterface $userTopicService,
    ) {

    }

    /**
     * @throws ForbiddenException
     * @throws InvalidArgumentException
     * @throws UserNotFoundException
     * @throws NotFoundException
     */
    public function handleFinishAndNotify(
        string $topic,
        JobRunStateChangedEvent $event
    ): void {
        $finished = new Finished(
            $event->getJobRunId(),
            $event->getJobName(),
            $event->getJobRunOwnerId(),
            $event->getNewState()
        );
        $this->publishService->publish(
            $topic,
            $finished
        );
    }

    public function handleFinishedWithErrors(
        int $jobRunId,
        int $ownerId,
        string $jobName
    ): void {
        $messages = [];
        $errorLogs = $this->jobRunErrorLogRepository->getLogsByJobRunId($jobRunId);
        foreach ($errorLogs as $errorLog) {
            $messages[] = $errorLog->getErrorMessage();
        }

        $this->publishService->publish(
            $this->userTopicService->getUserTopic($ownerId),
            new Finished(
                $jobRunId,
                $jobName,
                $ownerId,
                JobRunStates::FINISHED_WITH_ERRORS->value,
                $messages
            )
        );
    }
}
