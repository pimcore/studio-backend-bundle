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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Publishes the terminal "finished" Mercure event when an ownership management reassign/delete job
 * completes, so the frontend is notified immediately instead of relying on its polling fallback.
 *
 * @internal
 */
final readonly class OwnershipManagementSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventSubscriberServiceInterface $eventSubscriberService,
        private PublishServiceInterface $publishService,
        private UserTopicServiceInterface $userTopicService,
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
        if ($event->getJobName() !== Jobs::OWNERSHIP_MANAGEMENT_REASSIGN_OWNER->value &&
            $event->getJobName() !== Jobs::OWNERSHIP_MANAGEMENT_DELETE->value
        ) {
            return;
        }

        match ($event->getNewState()) {
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
    }
}
