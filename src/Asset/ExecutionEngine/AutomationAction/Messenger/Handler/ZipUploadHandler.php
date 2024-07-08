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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipUploadHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly UploadServiceInterface $uploadService,
        private readonly UserResolverInterface $userResolver,
        private readonly ZipServiceInterface $zipService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipUploadMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                EnvironmentVariables::PARENT_ID->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $archiveId = $validatedParameters->getSubject()->getType();
        $archiveExtractPath = $this->zipService->getTempFilePath(
            $archiveId,
            ZipServiceInterface::UPLOAD_ZIP_FOLDER_PATH
        );

        try {
            $archive = $this->zipService->getZipArchive(
                $archiveId,
                ZipServiceInterface::UPLOAD_ZIP_FILE_NAME,
                false
            );

            if (!$archive) {
                $this->abort($this->getAbortData(
                    Config::FILE_NOT_FOUND_FOR_JOB_RUN->value,
                    [
                        'type' => ElementTypes::TYPE_ARCHIVE,
                        'id' => $archiveId,
                    ],
                ));
            }

            $files = $this->zipService->getArchiveFiles(
                $archive,
                $archiveExtractPath
            );

            if (empty($files)) {
                $this->abort($this->getAbortData(
                    Config::FILE_NOT_FOUND_FOR_JOB_RUN->value,
                    [
                        'type' => ElementTypes::TYPE_ARCHIVE,
                        'id' => $archiveId,
                    ],
                ));
            }

            $childJobRunId = $this->uploadService->uploadAssetsAsynchronously(
                $user,
                $files,
                $validatedParameters->getEnvironmentData()[EnvironmentVariables::PARENT_ID->value],
                $this->zipService->getTempFileName(
                    $archiveId,
                    ZipServiceInterface::UPLOAD_ZIP_FOLDER_NAME
                )
            );

            $this->updateJobRunContext($jobRun, JobRunContext::CHILD_JOB_RUN->value, $childJobRunId);

        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ZIP_FILE_UPLOAD_FAILED_MESSAGE->value,
                ['message' => $exception->getMessage()],
            ));
        } finally {
            unlink($this->zipService->getTempFilePath($archiveId, ZipServiceInterface::UPLOAD_ZIP_FILE_PATH));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
