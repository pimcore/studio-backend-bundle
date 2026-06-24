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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\ChunkGeneratorTrait;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\ExecutionEngine\Messages\DeleteConfigurationsMessage;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\ExecutionEngine\Messages\ReassignOwnerMessage;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class JobService implements JobServiceInterface
{
    use ChunkGeneratorTrait;

    private const int BATCH_SIZE = 500;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function createReassignOwnerJob(string $type, array $ids, int $newOwnerId): int
    {
        $steps = [];
        foreach ($this->chunkGenerator($ids, self::BATCH_SIZE) as $batch) {
            $steps[] = new JobStep(
                JobSteps::REASSIGN_OWNER->value,
                ReassignOwnerMessage::class,
                '',
                [
                    StepConfig::CONFIGURATION_TYPE->value => $type,
                    StepConfig::NEW_OWNER_ID->value => $newOwnerId,
                    StepConfig::CONFIGURATION_IDS->value => $batch,
                ],
            );
        }

        return $this->startJob(Jobs::OWNERSHIP_MANAGEMENT_REASSIGN_OWNER->value, $steps);
    }

    public function createDeleteJob(string $type, array $ids): int
    {
        $steps = [];
        foreach ($this->chunkGenerator($ids, self::BATCH_SIZE) as $batch) {
            $steps[] = new JobStep(
                JobSteps::DELETE_CONFIGURATIONS->value,
                DeleteConfigurationsMessage::class,
                '',
                [
                    StepConfig::CONFIGURATION_TYPE->value => $type,
                    StepConfig::CONFIGURATION_IDS->value => $batch,
                ],
            );
        }

        return $this->startJob(Jobs::OWNERSHIP_MANAGEMENT_DELETE->value, $steps);
    }

    /**
     * @param JobStep[] $steps
     */
    private function startJob(string $jobName, array $steps): int
    {
        $job = new Job(name: $jobName, steps: $steps);

        return $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        )->getId();
    }
}
