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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\EventSubscriber;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Finished;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class ZipUploadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JobRunRepositoryInterface $jobRunRepository,
        private PublishServiceInterface $publishService,
        private UploadServiceInterface $uploadService,
        private ZipServiceInterface $zipService,
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class  => 'onStateChanged',
        ];
    }

    /**
     * @throws FilesystemException
     */
    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if ($event->getJobName() !== Jobs::ZIP_FILE_UPLOAD->value) {

            return;
        }

        $jobRun = $this->jobRunRepository->getJobRunById($event->getJobRunId());
        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->publishService->publish(
                Events::ZIP_UPLOAD_FINISHED->value,
                new Finished(
                    $event->getJobRunId(),
                    $event->getJobName(),
                    $event->getJobRunOwnerId(),
                    $event->getNewState(),
                    [JobRunContext::CHILD_JOB_RUN->value =>
                        $jobRun->getContext()[JobRunContext::CHILD_JOB_RUN->value] ?? null
                    ]
                )
            ),
            JobRunStates::FAILED->value => $this->cleanupData($jobRun),
            default => null,
        };
    }

    /**
     * @throws FilesystemException
     */
    private function cleanupData(JobRun $jobRun): void
    {
        $subject = $jobRun->getJob()?->getSelectedElements()[0];
        if ($subject === null) {

            return;
        }

        $this->uploadService->cleanupTemporaryUploadFiles(
            $this->zipService->getTempFilePath($subject->getType(), ZipServiceInterface::UPLOAD_ZIP_FOLDER_NAME)
        );
    }
}
