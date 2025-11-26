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

namespace Pimcore\Bundle\StudioBackendBundle\Export\EventSubscriber;

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFile;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Topics;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class XlsxCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventSubscriberServiceInterface $eventSubscriberService,
        private ExportServiceInterface $xlsxExportService
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
     * @throws ForbiddenException
     * @throws InvalidArgumentException
     * @throws UserNotFoundException
     * @throws NotFoundException
     * /
     */
    public function onStateChanged(JobRunStateChangedEvent $event): void
    {
        if ($event->getJobName() !== Jobs::CREATE_XLSX->value) {
            return;
        }

        match ($event->getNewState()) {
            JobRunStates::FINISHED->value => $this->eventSubscriberService->handleFinishAndNotify(
                Topics::STUDIO->value,
                $event
            ),
            JobRunStates::FAILED->value => $this->cleanupOnFail($event->getJobRunId()),
            default => null,
        };
    }

    /**
     * @throws FilesystemException
     */
    private function cleanupOnFail(int $jobRunId): void
    {
        $this->xlsxExportService->cleanupFileSystem(
            $jobRunId,
            ExportFile::XLSX_FOLDER_NAME->value,
            ExportFile::XLSX_FILE_NAME->value
        );
    }
}
