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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Progress;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Topics;
use function count;

/**
 * @internal
 */
trait HandlerProgressTrait
{
    private const int FREQUENCY = 10;

    private const int SEND_THRESHOLD = 99;

    private const string PROCESSED_ELEMENTS = 'processedElements';

    private const string ELEMENTS_PER_STEP = 'elementsPerStep';

    private const string CURRENT_STEP = 'currentStep';

    private const string TOTAL_STEPS = 'totalSteps';

    private function updateProgress(
        PublishServiceInterface $publishService,
        JobRun $jobRun,
        string $jobStepName,
        int $stepElements = 1
    ): void {
        $currentStep = $this->getCurrentStep($jobRun);
        $totalSteps = $this->getTotalSteps($jobRun);
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
            Topics::STUDIO->value,
            new Progress(
                $progress,
                // $currentStep + 1 because the current step is 0-based
                $currentStep + 1,
                $totalSteps,
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
