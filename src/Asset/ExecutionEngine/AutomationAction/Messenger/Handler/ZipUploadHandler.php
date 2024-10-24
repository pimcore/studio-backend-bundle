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
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipUploadHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    private const LOCAL_ZIP_FOLDER_NAME = 'studio-backend-local';

    public function __construct(
        private readonly FileSystem $fileSystem,
        private readonly PublishServiceInterface $publishService,
        private readonly StorageServiceInterface $storageService,
        private readonly UserResolverInterface $userResolver,
        private readonly UploadServiceInterface $uploadService,
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
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }
        $validatedParameters = $this->validateFullParameters(
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

        $archiveId = $validatedParameters->getSubject()->getType();
        $extractTargetPath = $this->zipService->getTempFilePath(
            $archiveId,
            ZipServiceInterface::UPLOAD_ZIP_FOLDER_NAME
        );
        $localExtractTargetPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' .
            $extractTargetPath . '/' .
            self::LOCAL_ZIP_FOLDER_NAME;

        try {
            $this->fileSystem->mkdir($localExtractTargetPath);

            $archive = $this->zipService->downloadZipFileFromFlysystem(
                $archiveId,
                ZipServiceInterface::UPLOAD_ZIP_FOLDER_NAME,
                ZipServiceInterface::UPLOAD_ZIP_FILE_NAME,
                $localExtractTargetPath
            );

            $elements = $this->zipService->extractArchiveFiles(
                $archive,
                $localExtractTargetPath
            );

            if (empty($elements)) {
                $this->abort($this->getAbortData(
                    Config::FILE_NOT_FOUND_FOR_JOB_RUN->value,
                    [
                        'type' => ElementTypes::TYPE_ARCHIVE,
                        'id' => $archiveId,
                    ],
                ));
            }

            $stepElementsForProgress = count($elements) + 1;
            $files = [];
            foreach ($elements as $element) {
                if (!$this->shouldBeExecuted($jobRun)) {
                    return;
                }
                $this->storageService->copyElementToFlysystem(
                    $element['path'],
                    $element['sourcePath'],
                    $extractTargetPath
                );

                if ($element['type'] === ElementTypes::TYPE_ASSET) {
                    $element['sourcePath'] = $extractTargetPath . '/' . $element['path'];
                    $files[] = $element;
                }

                $this->updateProgress(
                    $this->publishService,
                    $jobRun,
                    $this->getJobStep($message)->getName(),
                    $stepElementsForProgress
                );
            }
            $childJobRunId = $this->uploadService->uploadAssetsAsynchronously(
                $validatedParameters->getUser(),
                $files,
                $validatedParameters->getEnvironmentData()[EnvironmentVariables::PARENT_ID->value],
                $this->zipService->getTempFilePath(
                    $archiveId,
                    ZipServiceInterface::UPLOAD_ZIP_FOLDER_NAME
                )
            );

            $this->updateJobRunContext($jobRun, JobRunContext::CHILD_JOB_RUN->value, $childJobRunId);

            $this->updateProgress(
                $this->publishService,
                $jobRun,
                $this->getJobStep($message)->getName(),
                $stepElementsForProgress
            );

        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ZIP_FILE_UPLOAD_FAILED_MESSAGE->value,
                ['message' => $exception->getMessage()],
            ));
        } finally {
            $this->storageService->cleanUpLocalFolder($localExtractTargetPath);
        }
    }
}
