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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\RestoreItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\JobService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class JobServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testCreateRestoreJobWithSingleBatch(): void
    {
        $itemIds = [10, 20, 30];
        $capturedJob = null;

        $service = $this->createJobService(
            startJobExecution: function (Job $job, ?int $ownerId, string $context) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 42]);
            }
        );

        $result = $service->createRestoreJob($itemIds);

        $this->assertSame(42, $result);
        $this->assertNotNull($capturedJob);
        $this->assertSame(Jobs::RECYCLE_BIN_RESTORE->value, $capturedJob->getName());
        $this->assertEmpty($capturedJob->getSelectedElements());

        $steps = $capturedJob->getSteps();
        $this->assertCount(1, $steps);
        $this->assertSame(JobSteps::RESTORE_ITEMS->value, $steps[0]->getName());
        $this->assertSame(RestoreItemsMessage::class, $steps[0]->getMessageFQCN());
        $this->assertSame([10, 20, 30], $steps[0]->getConfig()[StepConfig::ITEMS_TO_RESTORE->value]);
    }

    /**
     * @throws Exception
     */
    public function testCreateRestoreJobUsesStopOnErrorContext(): void
    {
        $capturedContext = null;

        $service = $this->createJobService(
            startJobExecution: function (Job $job, ?int $ownerId, string $context) use (&$capturedContext) {
                $capturedContext = $context;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->createRestoreJob([1]);

        $this->assertSame(Config::CONTEXT_STOP_ON_ERROR->value, $capturedContext);
    }

    /**
     * @throws Exception
     */
    public function testCreateRestoreJobChunksItemsIntoMultipleSteps(): void
    {
        // Create 1200 items — should produce 3 steps (500 + 500 + 200)
        $itemIds = range(1, 1200);
        $capturedJob = null;

        $service = $this->createJobService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 99]);
            }
        );

        $service->createRestoreJob($itemIds);

        $steps = $capturedJob->getSteps();
        $this->assertCount(3, $steps);

        // First batch: items 1–500
        $batch1 = $steps[0]->getConfig()[StepConfig::ITEMS_TO_RESTORE->value];
        $this->assertCount(500, $batch1);
        $this->assertSame(1, $batch1[0]);
        $this->assertSame(500, $batch1[499]);

        // Second batch: items 501–1000
        $batch2 = $steps[1]->getConfig()[StepConfig::ITEMS_TO_RESTORE->value];
        $this->assertCount(500, $batch2);
        $this->assertSame(501, $batch2[0]);
        $this->assertSame(1000, $batch2[499]);

        // Third batch: items 1001–1200
        $batch3 = $steps[2]->getConfig()[StepConfig::ITEMS_TO_RESTORE->value];
        $this->assertCount(200, $batch3);
        $this->assertSame(1001, $batch3[0]);
        $this->assertSame(1200, $batch3[199]);

        // All steps have the same step name and message class
        foreach ($steps as $step) {
            $this->assertSame(JobSteps::RESTORE_ITEMS->value, $step->getName());
            $this->assertSame(RestoreItemsMessage::class, $step->getMessageFQCN());
        }
    }

    /**
     * @throws Exception
     */
    public function testCreateRestoreJobPreservesItemOrder(): void
    {
        $itemIds = [99, 3, 55, 12, 1];
        $capturedJob = null;

        $service = $this->createJobService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->createRestoreJob($itemIds);

        $batch = $capturedJob->getSteps()[0]->getConfig()[StepConfig::ITEMS_TO_RESTORE->value];
        $this->assertSame([99, 3, 55, 12, 1], $batch);
    }

    /**
     * @throws Exception
     */
    private function createJobService(?callable $startJobExecution = null): JobService
    {
        $jobExecutionAgent = $this->makeEmpty(JobExecutionAgentInterface::class, [
            'startJobExecution' => $startJobExecution ?? function () {
                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            },
        ]);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getId' => 1]),
        ]);

        return new JobService($jobExecutionAgent, $securityService);
    }
}
