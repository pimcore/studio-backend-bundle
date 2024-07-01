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

use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Events;
use Pimcore\Bundle\StudioBackendBundle\Asset\Mercure\Schema\ZipDownloadReady;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final readonly class ZipDownloadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PublishServiceInterface $publishService,
        private ZipServiceInterface $zipService
    ) {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            JobRunStateChangedEvent::class  => 'onStateChanged',
        ];
    }

    /**
     * @throws JsonEncodingException
     */
    public function onStateChanged(JobRunStateChangedEvent $event): void
    {

        if (
            $event->getNewState() === JobRunStates::FINISHED->value &&
            $event->getJobName() === Jobs::CREATE_ZIP->value
        ) {
            $this->publishService->publish(
                Events::ZIP_DOWNLOAD_READY->value,
                new ZipDownloadReady(
                    $event->getJobRunId(),
                    $this->zipService->getTempFilePath($event->getJobRunId(), ZipServiceInterface::ZIP_FILE_PATH),
                    $event->getJobRunOwnerId()
                )
            );
        }
    }
}
