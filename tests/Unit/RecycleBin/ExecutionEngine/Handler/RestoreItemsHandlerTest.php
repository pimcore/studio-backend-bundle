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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\ExecutionEngine\Handler;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Handler\RestoreItemsHandler;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\RestoreItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use ReflectionProperty;

/**
 * @internal
 */
final class RestoreItemsHandlerTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testRestoresAsTheJobRunOwnerAndCollectsTheRelocatedItemsInTheContext(): void
    {
        $owner = new User();
        $restoredIds = [];

        $recycleBinService = $this->createMock(RecycleBinServiceInterface::class);
        $recycleBinService->method('restoreItem')->willReturnCallback(
            function (int $id, ?UserInterface $user) use (&$restoredIds, $owner): array {
                $this->assertSame($owner, $user);
                $restoredIds[] = $id;

                return match ($id) {
                    3 => [new RelocatedItem(83, 'asset', '/__restored/image.jpg')],
                    5 => [new RelocatedItem(85, 'data-object', '/__restored/car_1')],
                    default => [],
                };
            }
        );

        $jobRun = $this->createJobRun(['relocatedItems' => [['id' => 1, 'elementType' => 'document', 'path' => '/x']]]);

        $this->createHandler($recycleBinService, $owner, $jobRun, [3, 4, 5])(new RestoreItemsMessage(1, 0));

        $this->assertSame([3, 4, 5], $restoredIds);
        $this->assertSame(
            [
                ['id' => 1, 'elementType' => 'document', 'path' => '/x'],
                ['id' => 83, 'elementType' => 'asset', 'path' => '/__restored/image.jpg'],
                ['id' => 85, 'elementType' => 'data-object', 'path' => '/__restored/car_1'],
            ],
            $jobRun->getContext()['relocatedItems']
        );
    }

    /**
     * @throws Exception
     */
    public function testLeavesTheContextWithoutRelocatedItemsWhenEveryParentExists(): void
    {
        $recycleBinService = $this->createMock(RecycleBinServiceInterface::class);
        $recycleBinService->method('restoreItem')->willReturn([]);

        $jobRun = $this->createJobRun([]);

        $this->createHandler($recycleBinService, new User(), $jobRun, [3, 4])(new RestoreItemsMessage(1, 0));

        $this->assertArrayNotHasKey('relocatedItems', $jobRun->getContext());
    }

    /**
     * @param int[] $itemIds
     */
    private function createHandler(
        RecycleBinServiceInterface $recycleBinService,
        User $owner,
        JobRun $jobRun,
        array $itemIds
    ): RestoreItemsHandler {
        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn($owner);

        $jobStep = $this->createMock(JobStepInterface::class);
        $jobStep->method('getName')->willReturn('step');
        $jobStep->method('getConfig')->willReturn([StepConfig::ITEMS_TO_RESTORE->value => $itemIds]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);
        $extractor->method('getJobStep')->willReturn($jobStep);

        $handler = new RestoreItemsHandler(
            $recycleBinService,
            $this->createMock(PublishServiceInterface::class),
            $this->createMock(UserTopicServiceInterface::class),
            $userResolver,
        );
        $handler->setJobRunRepository($this->createMock(JobRunRepositoryInterface::class));
        $handler->setJobRunExtractor($extractor);

        return $handler;
    }

    private function createJobRun(array $context): JobRun
    {
        $jobRun = new JobRun(7);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setContext($context);
        $jobRun->setJob(new Job(
            name: 'job',
            steps: [new JobStep('step', RestoreItemsMessage::class, '', [])],
        ));
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        return $jobRun;
    }
}
