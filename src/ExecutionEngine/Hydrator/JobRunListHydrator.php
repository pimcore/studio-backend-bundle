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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Hydrator;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun as JobRunEntity;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Schema\JobRun;
use function count;

/**
 * @internal
 */
final class JobRunListHydrator implements JobRunListHydratorInterface
{
    public function hydrate(JobRunEntity $jobRun): JobRun
    {
        $totalSteps = null;
        if ($jobRun->getState() == JobRunStates::RUNNING) {
            $totalSteps = $this->getTotalSteps($jobRun);
        }

        return new JobRun(
            id: $jobRun->getId(),
            ownerId: $jobRun->getOwnerId(),
            state: $jobRun->getState()->value,
            executionContext: $jobRun->getExecutionContext(),
            totalElements: $jobRun->getTotalElements(),
            currentMessage: $jobRun->getCurrentMessage(),
            currentStep: $jobRun->getCurrentStep(),
            totalSteps: $totalSteps,
            creationDate: $jobRun->getCreationDate(),
            modificationDate: $jobRun->getModificationDate(),
        );
    }

    private function getTotalSteps(JobRunEntity $jobRun): ?int
    {
        if ($jobRun->getState() !== JobRunStates::RUNNING) {
            return null;
        }

        $totalSteps = $jobRun->getContext()['totalSteps'] ?? null;
        if ($totalSteps !== null) {
            return $totalSteps;
        }

        return count($jobRun->getJob()?->getSteps() ?? []);
    }
}
