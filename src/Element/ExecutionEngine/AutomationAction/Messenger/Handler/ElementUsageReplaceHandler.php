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

namespace Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementUsageReplaceMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementUsageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ElementUsageReplaceHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    private ElementInterface $sourceElement;

    private ElementInterface $targetElement;

    public function __construct(
        private readonly ElementUsageServiceInterface $elementUsageService,
        private readonly UserResolverInterface $userResolver,
        private readonly PublishServiceInterface $publishService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ElementUsageReplaceMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $user = $this->getUser($jobRun);
        $this->initializeElements($message);
        $elementCount = 0;

        $elements = $jobRun->getJob()?->getSelectedElements();
        if ($elements === null) {
            $elements = [];
        }

        foreach ($elements as $elementData) {
            $element = $this->elementUsageService->getElementById(
                $elementData->getType(),
                $elementData->getId()
            );

            $this->elementUsageService->replaceElementUsage(
                $this->sourceElement,
                $this->targetElement,
                $element,
                $user
            );

            $this->updateProgress(
                $this->publishService,
                $jobRun,
                $this->getJobStep($message)->getName(),
                ++$elementCount
            );
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_ID
        );
        $this->stepConfiguration->setAllowedTypes(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_ID, 'int'
        );
        $this->stepConfiguration->setRequired(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_ID
        );
        $this->stepConfiguration->setAllowedTypes(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_ID, 'int'
        );
        $this->stepConfiguration->setRequired(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_TYPE
        );
        $this->stepConfiguration->setAllowedTypes(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_TYPE, 'string'
        );
        $this->stepConfiguration->setRequired(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_TYPE
        );
        $this->stepConfiguration->setAllowedTypes(
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_TYPE, 'string'
        );
    }

    private function initializeElements(ElementUsageReplaceMessage $message): void
    {
        $elementSourceId = $this->extractConfigFieldFromJobStepConfig(
            $message,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_ID
        );
        $elementSourceType = $this->extractConfigFieldFromJobStepConfig(
            $message,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_TYPE
        );
        $elementTargetId = $this->extractConfigFieldFromJobStepConfig(
            $message,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_ID
        );
        $elementTargetType = $this->extractConfigFieldFromJobStepConfig(
            $message,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_TYPE
        );

        $this->sourceElement = $this->elementUsageService->getElementById(
            $elementSourceType,
            $elementSourceId
        );

        $this->targetElement = $this->elementUsageService->getElementById(
            $elementTargetType,
            $elementTargetId
        );
    }

    /**
     * @throws Exception
     */
    private function getUser(JobRun $jobRun): User
    {
        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                [
                    'userId' => $jobRun->getOwnerId(),
                ]
            ));
        }

        return $user;
    }
}
