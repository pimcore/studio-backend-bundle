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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ExecutionEngine\Util\Trait;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use ReflectionProperty;

/**
 * @internal
 */
final class HandlerProgressTraitTest extends Unit
{
    private const int ELEMENTS = 500;

    public function testPersistsEveryUpdateByDefault(): void
    {
        $jobRun = $this->createJobRun();
        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->expects($this->exactly(self::ELEMENTS))->method('update');

        $handler = $this->createHandler($repository);
        for ($i = 0; $i < self::ELEMENTS; $i++) {
            $handler->progress($this->createPublishService(), $this->createUserTopicService(), $jobRun, 'step');
        }

        $this->assertSame(self::ELEMENTS, $jobRun->getContext()['processedElements']);
    }

    public function testDoesNotPersistEachElementOfABatch(): void
    {
        $jobRun = $this->createJobRun();
        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->expects($this->never())->method('update');

        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->expects($this->atLeastOnce())->method('publish');

        $handler = $this->createHandler($repository);
        for ($i = 0; $i < self::ELEMENTS; $i++) {
            $handler->progress(
                $publishService,
                $this->createUserTopicService(),
                $jobRun,
                'step',
                self::ELEMENTS,
                persist: false
            );
        }

        $this->assertSame(self::ELEMENTS, $jobRun->getContext()['processedElements']);
    }

    private function createHandler(JobRunRepositoryInterface $repository): object
    {
        $handler = new class() extends AbstractHandler {
            use HandlerProgressTrait;

            public function progress(
                PublishServiceInterface $publishService,
                UserTopicServiceInterface $userTopicService,
                JobRun $jobRun,
                string $jobStepName,
                int $stepElements = 1,
                bool $persist = true
            ): void {
                $this->updateProgress(
                    $publishService,
                    $userTopicService,
                    $jobRun,
                    $jobStepName,
                    $stepElements,
                    persist: $persist
                );
            }
        };
        $handler->setJobRunRepository($repository);

        return $handler;
    }

    private function createJobRun(): JobRun
    {
        $jobRun = new JobRun(1);
        $jobRun->setCurrentStep(0);
        $jobRun->setContext([]);
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        return $jobRun;
    }

    private function createPublishService(): PublishServiceInterface
    {
        return $this->createMock(PublishServiceInterface::class);
    }

    private function createUserTopicService(): UserTopicServiceInterface
    {
        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        return $userTopicService;
    }
}
