<?php

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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Progress;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Events;
use function count;

trait HandlerProgressTrait
{
    private const FREQUENCY = 10;

    private const SEND_THRESHOLD = 99;

    private const PROCESSED_ELEMENTS = 'processedElements';

    private const ELEMENTS_PER_STEP = 'elementsPerStep';

    private const CURRENT_STEP = 'currentStep';

    private const TOTAL_STEPS = 'totalSteps';

    private function updateProgress(
        PublishServiceInterface $publishService,
        JobRun $jobRun,
        string $jobStepName,
        int $stepElements = 1
    ): void {
        $currentStep = $this->getCurrentStep($jobRun);
        $totalEvents = $this->getElementsPerStep($jobRun, $stepElements);

        $processedElements = $jobRun->getContext()[self::PROCESSED_ELEMENTS] ?? 0;
        $processedElements++;
        $this->updateJobRunContext($jobRun, self::PROCESSED_ELEMENTS, $processedElements);
        $updateFrequency = max(1, (int)($totalEvents / self::FREQUENCY));

        $progress = (int)($processedElements / $totalEvents * 100);

        if (($progress < self::SEND_THRESHOLD) && $processedElements % $updateFrequency !== 0) {
            return;
        }

        $publishService->publish(
            Events::HANDLER_PROGRESS->value,
            new Progress(
                $progress,
                // $currentStep + 1 because the current step is 0-based
                $currentStep + 1,
                $this->getTotalSteps($jobRun),
                $jobStepName,
                $jobRun->getJob()?->getName() ?? '',
                $jobRun->getId(),
                $jobRun->getOwnerId(),
            )
        );
    }

    private function getTotalSteps(JobRun $jobRun): int
    {
        $totalSteps = $jobRun->getContext()[self::TOTAL_STEPS] ?? null;
        if ($totalSteps !== null) {
            return $totalSteps;
        }

        $totalSteps = count($jobRun->getJob()?->getSteps() ?? []);
        $this->updateJobRunContext($jobRun, self::TOTAL_STEPS, $totalSteps);

        return $totalSteps;
    }

    private function getCurrentStep(JobRun $jobRun): int
    {
        $currentStep = $jobRun->getContext()[self::CURRENT_STEP] ?? null;

        if ($jobRun->getCurrentStep() === $currentStep) {
            return $currentStep;
        }

        $currentStep = $jobRun->getCurrentStep();
        $this->updateJobRunContext($jobRun, self::PROCESSED_ELEMENTS, 0);
        $this->updateJobRunContext($jobRun, self::ELEMENTS_PER_STEP, null);
        $this->updateJobRunContext($jobRun, self::CURRENT_STEP, $currentStep);

        return $currentStep;
    }

    private function getElementsPerStep(JobRun $jobRun, int $stepElements): int
    {
        $contextElements = $jobRun->getContext()[self::ELEMENTS_PER_STEP] ?? null;
        if ($contextElements !== null) {
            return $contextElements;
        }

        $elementsPerStep = $stepElements;
        if ($jobRun->getTotalElements() > 0) {
            $elementsPerStep = $jobRun->getTotalElements() * $elementsPerStep;
        }

        $this->updateJobRunContext($jobRun, self::ELEMENTS_PER_STEP, $elementsPerStep);

        return $elementsPerStep;
    }
}
