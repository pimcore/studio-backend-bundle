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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class RestoreItemsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PublishServiceInterface $publishService,
        private UserTopicServiceInterface $userTopicService,
        private JobRunRepositoryInterface $jobRunRepository,
        private TranslatorServiceInterface $translatorService,
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
            $event->getNewState() === JobRunStates::FINISHED->value &&
            $event->getJobName() === Jobs::RECYCLE_BIN_RESTORE->value
        ) {
            $this->publishService->publish(
                $this->userTopicService->getUserTopic($event->getJobRunOwnerId()),
                new Finished(
                    $event->getJobRunId(),
                    $event->getJobName(),
                    $event->getJobRunOwnerId(),
                    $event->getNewState(),
                    $this->getRelocatedItemMessages($event->getJobRunId())
                )
            );
        }
    }

    /**
     * @return string[]
     */
    private function getRelocatedItemMessages(int $jobRunId): array
    {
        $context = $this->jobRunRepository->getJobRunById($jobRunId)->getContext();
        $messages = [];
        foreach ($context[JobRunContext::RELOCATED_ITEMS->value] ?? [] as $relocatedItem) {
            $messages[] = $this->translatorService->translate(
                Config::RECYCLE_BIN_RESTORE_RELOCATED->value,
                [
                    '%id%' => $relocatedItem['id'],
                    '%type%' => $relocatedItem['elementType'],
                    '%path%' => $relocatedItem['path'],
                ]
            );
        }

        return $messages;
    }
}
