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

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\Progress;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Events;

trait HandlerProgressTrait
{
    private function updateProgress(JobRun $jobRun, PublishServiceInterface $publishService): void
    {
        $totalEvents = $jobRun->getJob()?->getEnvironmentData()['totalEvents'];
        $processedElements = $jobRun->getContext()['processedElements'] ?? 0;

        $processedElements++;

        $this->updateJobRunContext($jobRun, 'processedElements', $processedElements);

        $progress = (int)($processedElements / $totalEvents * 100);

        if(($progress < 99) && $processedElements % 10 !== 0) {
            return;
        }

        $publishService->publish(
            Events::HANDLER_PROGRESS->value,
            new Progress(
                $progress,
                $jobRun->getOwnerId(),
                $jobRun->getId(),
                $jobRun->getJob()?->getName() ?? ''
            )
        );
    }
}
