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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipDownloadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipDownloadHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly ElementServiceInterface $elementService,
        private readonly UserResolverInterface $userResolver,
        private readonly ZipServiceInterface $zipService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipDownloadMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $jobRunId = $jobRun->getId();
        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                [
                    'userId' => $jobRun->getOwnerId(),
                ]
            ));
        }

        $assetIds = $this->extractConfigFieldFromJobStepConfig($message, ZipServiceInterface::ASSETS_TO_ZIP);
        $assetCount = count($assetIds);
        if ($assetCount === 0) {
            $this->abort($this->getAbortData(
                Config::NO_ASSETS_FOUND_FOR_JOB_RUN->value,
                [
                    'jobRunId' => $jobRunId,
                ]
            ));
        }
        $archiveLocalPath = $this->zipService->getTempFilePath($jobRunId, ZipServiceInterface::DOWNLOAD_ZIP_FILE_PATH);
        $archive = $this->zipService->createLocalArchive(
            $archiveLocalPath,
            true
        );

        foreach ($assetIds as $assetId) {
            if (!$this->shouldBeExecuted($jobRun)) {
                return;
            }
            $asset = $this->getElementById(
                new ElementDescriptor(
                    ElementTypes::TYPE_ASSET,
                    $assetId
                ),
                $user,
                $this->elementService
            );

            if (!$asset instanceof Asset || $asset->getType() === ElementTypes::TYPE_FOLDER) {
                $this->abort($this->getAbortData(
                    Config::ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED->value,
                    [
                        'folderId' => $asset->getId(),
                    ]
                ));

                return;
            }

            $this->zipService->addFile($archive, $asset);

            $this->updateProgress(
                $this->publishService,
                $jobRun,
                $this->getJobStep($message)->getName(),
                $assetCount + 1
            );
        }

        $archive->close();
        $this->zipService->copyZipFileToFlysystem(
            (string)$jobRunId,
            ZipServiceInterface::DOWNLOAD_ZIP_FOLDER_NAME,
            ZipServiceInterface::DOWNLOAD_ZIP_FILE_NAME,
            $archiveLocalPath,
        );

        $this->updateProgress(
            $this->publishService,
            $jobRun,
            $this->getJobStep($message)->getName(),
            $assetCount + 1
        );
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(ZipServiceInterface::ASSETS_TO_ZIP);
        $this->stepConfiguration->setAllowedTypes(ZipServiceInterface::ASSETS_TO_ZIP, 'array');
    }
}
