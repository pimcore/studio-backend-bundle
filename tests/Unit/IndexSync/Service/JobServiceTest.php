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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\IndexSync\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\ExecutionEngine\Messages\SyncIndexMessage;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\Service\JobService;
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
    public function testCreateSyncJobStartsASingleStepJobForTheCurrentUser(): void
    {
        $capturedJob = null;
        $capturedOwnerId = null;
        $capturedContext = null;

        $jobExecutionAgent = $this->makeEmpty(JobExecutionAgentInterface::class, [
            'startJobExecution' => function (Job $job, ?int $ownerId, string $context) use (
                &$capturedJob,
                &$capturedOwnerId,
                &$capturedContext
            ) {
                $capturedJob = $job;
                $capturedOwnerId = $ownerId;
                $capturedContext = $context;

                return $this->makeEmpty(JobRun::class, ['getId' => 42]);
            },
        ]);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getId' => 7]),
        ]);

        $jobRunId = (new JobService($jobExecutionAgent, $securityService))->createSyncJob();

        $this->assertSame(42, $jobRunId);
        $this->assertSame(7, $capturedOwnerId);
        $this->assertSame(Config::CONTEXT_CONTINUE_ON_ERROR->value, $capturedContext);
        $this->assertSame(Jobs::INDEX_SYNC->value, $capturedJob->getName());
        $this->assertCount(1, $capturedJob->getSteps());
        $this->assertSame(JobSteps::SYNC_INDEX->value, $capturedJob->getSteps()[0]->getName());
        $this->assertSame(SyncIndexMessage::class, $capturedJob->getSteps()[0]->getMessageFQCN());
    }
}
