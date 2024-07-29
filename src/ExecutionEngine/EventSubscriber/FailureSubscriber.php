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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function in_array;

/**
 * @internal
 */
final readonly class FailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PublishServiceInterface $publishService,
        private JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private JobRunRepositoryInterface $jobRunRepository
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class  => 'onStateChanged',
        ];
    }

    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if (
            $event->getNewState() === JobRunStates::FAILED->value
        ) {
            $jobRunId = $event->getJobRunId();
            $jobRun = $this->jobRunRepository->getJobRunById($jobRunId);
            if (!in_array(
                $jobRun->getExecutionContext(),
                [Config::CONTEXT_CONTINUE_ON_ERROR->value, Config::CONTEXT_STOP_ON_ERROR->value],
                true
            )) {
                return;
            }

            $log = $this->jobRunErrorLogRepository->getLogsByJobRunId(
                $jobRunId,
                null,
                [],
                1
            );

            $this->publishService->publish(
                Events::FAILED->value,
                new Finished(
                    $jobRunId,
                    $event->getJobName(),
                    $event->getJobRunOwnerId(),
                    $event->getNewState(),
                    [$log[0]->getErrorMessage()]
                )
            );
        }
    }
}
