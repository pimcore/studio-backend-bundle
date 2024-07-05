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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCleanupMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipCleanupHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly ZipServiceInterface $zipService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipCleanupMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $archiveId = $validatedParameters->getSubject()->getType();
        $archiveFolder = $this->zipService->getTempFileName(
            $archiveId,
            ZipServiceInterface::UPLOAD_ZIP_FOLDER_NAME
        );

        try {
            $this->zipService->cleanUpArchive(
                $archiveId,
                $archiveFolder
            );
        } catch (Exception|FilesystemException $exception) {
            $this->abort($this->getAbortData(
                Config::ZIP_FILE_UPLOAD_FAILED_MESSAGE->value,
                [
                    'directory' => $archiveFolder,
                    'message' => $exception->getMessage(),
                ],
            ));
        } finally {
            unlink($this->zipService->getTempFilePath($archiveId, ZipServiceInterface::UPLOAD_ZIP_FILE_PATH));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
