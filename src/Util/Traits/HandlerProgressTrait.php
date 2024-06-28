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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use function count;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Progress;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Events;

trait HandlerProgressTrait
{
    private const FREQUENCY = 10;

    private const SEND_THRESHOLD = 99;

    private const PROCESSING_EVENTS = 'processedEvents';

    private const TOTAL_EVENTS = 'totalEvents';

    private function updateProgress(
        PublishServiceInterface $publishService,
        JobRun $jobRun,
        string $jobStepName = ''
    ): void {
        $totalEvents = $this->getTotalEvents($jobRun);

        $processedElements = $jobRun->getContext()[self::PROCESSING_EVENTS] ?? 0;

        $processedElements++;

        $this->updateJobRunContext($jobRun, self::PROCESSING_EVENTS, $processedElements);

        $updateFrequency = max(1, (int)($totalEvents / self::FREQUENCY));

        $progress = (int)($processedElements / $totalEvents * 100);

        if (($progress < self::SEND_THRESHOLD) && $processedElements % $updateFrequency !== 0) {
            return;
        }

        $publishService->publish(
            Events::HANDLER_PROGRESS->value,
            new Progress(
                $progress,
                $jobRun->getOwnerId(),
                $jobRun->getId(),
                $jobRun->getJob()?->getName() ?? '',
                $jobStepName,
            )
        );
    }

    private function getTotalEvents(JobRun $jobRun): int
    {
        $steps = count($jobRun->getJob()?->getSteps() ?? []);

        if (isset($jobRun->getContext()[self::TOTAL_EVENTS])) {
            return $jobRun->getContext()[self::TOTAL_EVENTS];
        }

        $totalEvents = $steps * $jobRun->getTotalElements();

        $this->updateJobRunContext($jobRun, self::TOTAL_EVENTS, $steps * $jobRun->getTotalElements());

        return $totalEvents;
    }
}
