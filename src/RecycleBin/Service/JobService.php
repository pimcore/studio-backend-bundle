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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\ChunkGeneratorTrait;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Messages\RestoreItemsMessage;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Element\ElementDescriptor;
use function array_map;

/**
 * @internal
 */
final readonly class JobService implements JobServiceInterface
{
    use ChunkGeneratorTrait;

    private const int RESTORE_BATCH_SIZE = 500;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function createJob(string $jobName, string $jobStepName, string $messageFQCN, array $items): int
    {
        $job = new Job(
            name: $jobName,
            steps: [
                new JobStep($jobStepName, $messageFQCN, '', []),
            ],
            selectedElements: array_map(
                static fn (int $id) => new ElementDescriptor(
                    'Item',
                    $id
                ),
                $items
            )
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    public function createRestoreJob(array $sortedItemIds): int
    {
        $steps = [];
        foreach ($this->chunkGenerator($sortedItemIds, self::RESTORE_BATCH_SIZE) as $batch) {
            $steps[] = new JobStep(
                JobSteps::RESTORE_ITEMS->value,
                RestoreItemsMessage::class,
                '',
                [StepConfig::ITEMS_TO_RESTORE->value => $batch],
            );
        }

        $job = new Job(
            name: Jobs::RECYCLE_BIN_RESTORE->value,
            steps: $steps,
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }
}
