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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\EventSubscriber\RestoreItemsSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use function sprintf;

/**
 * @internal
 */
final class RestoreItemsSubscriberTest extends Unit
{
    public function testFinishedMessageCarriesOneTranslatedLinePerRelocatedItem(): void
    {
        $finished = $this->publishFinished([
            'relocatedItems' => [
                ['id' => 83, 'elementType' => 'asset', 'path' => '/__restored/image.jpg'],
                ['id' => 85, 'elementType' => 'data-object', 'path' => '/__restored/car_1'],
            ],
        ]);

        $this->assertSame(
            [
                'studio_ee_recycle_bin_restore_relocated asset 83 /__restored/image.jpg',
                'studio_ee_recycle_bin_restore_relocated data-object 85 /__restored/car_1',
            ],
            $finished->getMessages()
        );
        $this->assertSame(JobRunStates::FINISHED->value, $finished->getStatus());
    }

    public function testFinishedMessageIsEmptyWithoutRelocatedItems(): void
    {
        $this->assertSame([], $this->publishFinished([])->getMessages());
    }

    public function testIgnoresOtherJobs(): void
    {
        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->expects($this->never())->method('publish');

        $this->createSubscriber($publishService, [])->onStateChanged(
            new JobRunStateChangedEvent(
                5,
                Jobs::RECYCLE_BIN_DELETE->value,
                1,
                JobRunStates::RUNNING->value,
                JobRunStates::FINISHED->value
            )
        );
    }

    private function publishFinished(array $context): Finished
    {
        $published = null;
        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->expects($this->once())->method('publish')->willReturnCallback(
            static function (string $topic, Finished $finished) use (&$published): void {
                $published = $finished;
            }
        );

        $this->createSubscriber($publishService, $context)->onStateChanged(
            new JobRunStateChangedEvent(
                5,
                Jobs::RECYCLE_BIN_RESTORE->value,
                1,
                JobRunStates::RUNNING->value,
                JobRunStates::FINISHED->value
            )
        );

        $this->assertInstanceOf(Finished::class, $published);

        return $published;
    }

    private function createSubscriber(PublishServiceInterface $publishService, array $context): RestoreItemsSubscriber
    {
        $jobRun = new JobRun(1);
        $jobRun->setContext($context);

        $jobRunRepository = $this->createMock(JobRunRepositoryInterface::class);
        $jobRunRepository->expects($this->atMost(1))->method('getJobRunById')->with(5)->willReturn($jobRun);

        $translatorService = $this->createMock(TranslatorServiceInterface::class);
        $translatorService->method('translate')->willReturnCallback(
            static fn (string $key, array $params): string => sprintf(
                '%s %s %s %s',
                $key,
                $params['%type%'],
                $params['%id%'],
                $params['%path%']
            )
        );

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        return new RestoreItemsSubscriber($publishService, $userTopicService, $jobRunRepository, $translatorService);
    }
}
