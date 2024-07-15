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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipCreationHandler extends AbstractHandler
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
    public function __invoke(ZipCreationMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                [
                    'userId' => $jobRun->getOwnerId(),
                ]
            ));
        }

        $assetId = $this->extractConfigFieldFromJobStepConfig($message, ZipServiceInterface::ASSET_TO_ZIP);
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

        $archive = $this->zipService->getZipArchive($jobRun->getId());
        if (!$archive) {
            $this->abort($this->getAbortData(
                Config::FILE_NOT_FOUND_FOR_JOB_RUN->value,
                [
                    'type' => 'zip',
                    'jobRunId' => $jobRun->getId(),
                ]
            ));
        }

        $this->zipService->addFile($archive, $asset);
        $archive->close();

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(ZipServiceInterface::ASSET_TO_ZIP);
        $this->stepConfiguration->setAllowedTypes(ZipServiceInterface::ASSET_TO_ZIP, 'int');
    }
}
