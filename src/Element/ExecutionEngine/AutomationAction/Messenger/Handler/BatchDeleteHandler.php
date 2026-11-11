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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\BatchDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class BatchDeleteHandler extends AbstractHandler
{
    use ElementProviderTrait;
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementDeleteServiceInterface $elementDeleteService,
        private readonly PublishServiceInterface $publishService,
        private readonly ServiceResolverInterface $elementService,
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(BatchDeleteMessage $message): void
    {
        if (!$this->shouldBeExecuted($this->getJobRun($message))) {
            return;
        }

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

        $elementType = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::ELEMENT_TYPE_TO_BATCH_DELETE->value
        );
        $items = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::ITEMS_TO_BATCH_DELETE->value
        );
        $totalItems = count($items);
        $stepName = $this->getJobStep($message)->getName();

        foreach ($items as $elementId) {
            $element = $this->elementService->getElementById(
                $this->getCoreElementType($elementType),
                $elementId
            );

            if ($element instanceof ElementInterface) {
                $this->deleteBatchElement($element, $user, $elementType);
            }

            $this->updateProgress(
                $this->publishService,
                $this->userTopicService,
                $jobRun,
                $stepName,
                $totalItems,
                100,
            );
        }
    }

    /**
     * @throws Exception
     */
    private function deleteBatchElement(
        ElementInterface $element,
        UserInterface $user,
        string $elementType
    ): void {
        try {
            $this->elementDeleteService->processBatchDelete($element, $user, $elementType);
        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_BATCH_DELETE_FAILED_MESSAGE->value,
                [
                    'type' => $element->getType(),
                    'id' => $element->getId(),
                    'message' => $exception->getMessage(),
                ],
            ));
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ITEMS_TO_BATCH_DELETE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ITEMS_TO_BATCH_DELETE->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_TYPE_TO_BATCH_DELETE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_TYPE_TO_BATCH_DELETE->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
    }
}
