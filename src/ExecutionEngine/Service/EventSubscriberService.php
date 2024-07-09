<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;

/**
 * @internal
 */
final readonly class EventSubscriberService implements EventSubscriberServiceInterface
{
    public function __construct(
        private JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private PublishServiceInterface $publishService,
    ) {

    }

    public function handleFinishedWithErrors(
        int $jobRunId,
        int $ownerId,
        string $jobName
    ): void {
        $messages = [];
        $errorLogs = $this->jobRunErrorLogRepository->getLogsByJobRunId($jobRunId);
        foreach ($errorLogs as $errorLog) {
            $messages[] = $errorLog->getErrorMessage();
        }

        $this->publishService->publish(
            Events::FINISHED_WITH_ERRORS->value,
            new Finished(
                $jobRunId,
                $jobName,
                $ownerId,
                JobRunStates::FINISHED_WITH_ERRORS->value,
                $messages
            )
        );
    }
}
