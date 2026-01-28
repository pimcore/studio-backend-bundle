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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
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
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
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
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateFullParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                EnvironmentVariables::PARENT_ID->value,
                EnvironmentVariables::UPLOAD_FOLDER_LOCATION->value,
            ]
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

        $this->updateProgress($this->publishService, $this->userTopicService, $jobRun, $this->getJobStep($message)->getName());
    }
}
