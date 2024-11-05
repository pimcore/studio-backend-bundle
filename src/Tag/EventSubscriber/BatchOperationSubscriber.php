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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Mercure\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class BatchOperationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventSubscriberServiceInterface $eventSubscriberService,
        private PublishServiceInterface $publishService,
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
        if ($event->getJobName() !== Jobs::BATCH_TAG_ASSIGN->value &&
            $event->getJobName() !== Jobs::BATCH_TAG_REPLACE->value
        ) {
            return;
        }

        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                $this->getEventName($event->getJobName()),
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

    private function getEventName(string $jobName): string
    {
        return $jobName === Jobs::BATCH_TAG_ASSIGN->value ?
            Events::TAG_ASSIGNMENT_FINISHED->value :
            Events::TAG_REPLACEMENT_FINISHED->value;
    }
}
