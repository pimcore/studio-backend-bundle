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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function dirname;

/**
 * @internal
 */
#[AsMessageHandler]
final class AssetUploadHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly StorageServiceInterface $storageService,
        private readonly UserResolverInterface $userResolver,
        private readonly UploadServiceInterface $uploadService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(AssetUploadMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                EnvironmentVariables::PARENT_ID->value,
                EnvironmentVariables::UPLOAD_FOLDER_NAME->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $environmentVariables = $validatedParameters->getEnvironmentData();

        try {
            $element = $validatedParameters->getSubject()->getType();
            $fileData = json_decode($element, true, 512, JSON_THROW_ON_ERROR);
            $folderLocation = dirname($fileData['path']);
            $parentId = $environmentVariables[EnvironmentVariables::PARENT_ID->value];

            if ($folderLocation !== '.') {
                $parentId = $this->uploadService->uploadParentFolder(
                    $fileData['path'],
                    $parentId,
                    $user,
                );
            }

            $this->uploadService->uploadAsset(
                $parentId,
                $fileData['name'],
                $fileData['sourcePath'],
                $user,
                true
            );
        } catch (Exception|FilesystemException $exception) {
            $this->abort($this->getAbortData(
                Config::ASSET_UPLOAD_FAILED_MESSAGE->value,
                ['message' => $exception->getMessage()],
            ));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
