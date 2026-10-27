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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetFolderCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Clones all folders of a recursive asset clone in one message. The folders are given parents first, so the target of
 * every folder already exists when it is cloned, no matter how many workers process the job.
 *
 * @internal
 */
#[AsMessageHandler]
final class AssetFolderCloneHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementServiceInterface $elementService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly CloneServiceInterface $cloneService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(AssetFolderCloneMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                ['userId' => $jobRun->getOwnerId()]
            ));
        }

        $environmentData = $jobRun->getJob()?->getEnvironmentData() ?? [];
        foreach ([EnvironmentVariables::ORIGINAL_PARENT_ID, EnvironmentVariables::PARENT_ID] as $variable) {
            if (!isset($environmentData[$variable->value])) {
                $this->abort($this->getAbortData(
                    Config::ENVIRONMENT_VARIABLE_NOT_FOUND->value,
                    ['variable' => $variable->value]
                ));
            }
        }

        $folderIds = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::FOLDERS_TO_CLONE->value);
        foreach ($folderIds as $folderId) {
            $source = $this->getElementById(
                new ElementDescriptor(ElementTypes::TYPE_ASSET, $folderId),
                $user,
                $this->elementService
            );
            if (!$source instanceof Asset) {
                continue;
            }

            $parent = $this->cloneService->getNewCloneTarget(
                $user,
                $source,
                $environmentData[EnvironmentVariables::ORIGINAL_PARENT_ID->value],
                $environmentData[EnvironmentVariables::PARENT_ID->value],
            );
            $this->cloneService->cloneElement($source, $parent, $user);
        }

        $this->updateProgress(
            $this->publishService,
            $this->userTopicService,
            $jobRun,
            $this->getJobStep($message)->getName()
        );
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::FOLDERS_TO_CLONE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::FOLDERS_TO_CLONE->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }
}
