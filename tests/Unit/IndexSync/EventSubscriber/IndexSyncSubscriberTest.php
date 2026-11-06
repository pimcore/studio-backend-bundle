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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\IndexSync\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\EventSubscriber\IndexSyncSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;

/**
 * @internal
 */
final class IndexSyncSubscriberTest extends Unit
{
    private const JOB_RUN_ID = 5;

    private const OWNER_ID = 7;

    private const TOPIC = 'topic-of-user-7';

    public function testPublishesTheFinishedEventToTheTopicOfTheOwner(): void
    {
        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->expects($this->once())
            ->method('publish')
            ->with(
                self::TOPIC,
                $this->callback(static fn (mixed $data): bool => $data instanceof Finished
                    && $data->getJobRunId() === self::JOB_RUN_ID
                    && $data->getJobRunName() === Jobs::INDEX_SYNC->value
                    && $data->getOwnerId() === self::OWNER_ID
                    && $data->getStatus() === JobRunStates::FINISHED->value)
            );

        $eventSubscriberService = $this->createMock(EventSubscriberServiceInterface::class);
        $eventSubscriberService->expects($this->never())->method('handleFinishedWithErrors');

        $this->createSubscriber($eventSubscriberService, $publishService)->onStateChanged(
            $this->createEvent(Jobs::INDEX_SYNC->value, JobRunStates::FINISHED->value)
        );
    }

    public function testDelegatesAFinishedWithErrorsJobRunToTheEventSubscriberService(): void
    {
        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->expects($this->never())->method('publish');

        $eventSubscriberService = $this->createMock(EventSubscriberServiceInterface::class);
        $eventSubscriberService->expects($this->once())
            ->method('handleFinishedWithErrors')
            ->with(self::JOB_RUN_ID, self::OWNER_ID, Jobs::INDEX_SYNC->value);

        $this->createSubscriber($eventSubscriberService, $publishService)->onStateChanged(
            $this->createEvent(Jobs::INDEX_SYNC->value, JobRunStates::FINISHED_WITH_ERRORS->value)
        );
    }

    /**
     * @dataProvider ignoredEventsProvider
     */
    public function testIgnoresOtherJobsAndStatesThatAreNotFinished(string $jobName, string $state): void
    {
        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->expects($this->never())->method('publish');

        $eventSubscriberService = $this->createMock(EventSubscriberServiceInterface::class);
        $eventSubscriberService->expects($this->never())->method('handleFinishedWithErrors');

        $this->createSubscriber($eventSubscriberService, $publishService)->onStateChanged(
            $this->createEvent($jobName, $state)
        );
    }

    public function ignoredEventsProvider(): array
    {
        return [
            'other job finished' => [Jobs::OWNERSHIP_MANAGEMENT_DELETE->value, JobRunStates::FINISHED->value],
            'other job finished with errors' => [
                Jobs::OWNERSHIP_MANAGEMENT_DELETE->value,
                JobRunStates::FINISHED_WITH_ERRORS->value,
            ],
            'still running' => [Jobs::INDEX_SYNC->value, JobRunStates::RUNNING->value],
            'not started' => [Jobs::INDEX_SYNC->value, JobRunStates::NOT_STARTED->value],
            'failed' => [Jobs::INDEX_SYNC->value, JobRunStates::FAILED->value],
        ];
    }

    public function testSubscribesToTheStateChangesOfJobRuns(): void
    {
        $this->assertSame(
            [JobRunStateChangedEvent::class => 'onStateChanged'],
            IndexSyncSubscriber::getSubscribedEvents()
        );
    }

    private function createEvent(string $jobName, string $newState): JobRunStateChangedEvent
    {
        return new JobRunStateChangedEvent(
            self::JOB_RUN_ID,
            $jobName,
            self::OWNER_ID,
            JobRunStates::RUNNING->value,
            $newState
        );
    }

    private function createSubscriber(
        EventSubscriberServiceInterface $eventSubscriberService,
        PublishServiceInterface $publishService,
    ): IndexSyncSubscriber {
        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->with(self::OWNER_ID)->willReturn(self::TOPIC);

        return new IndexSyncSubscriber($eventSubscriberService, $publishService, $userTopicService);
    }
}
