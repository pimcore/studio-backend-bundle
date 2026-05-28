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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class ElementDeleteHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementDeleteServiceInterface $elementDeleteService,
        private readonly ElementServiceInterface $elementService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ElementDeleteMessage $message): void
    {
        if (!$this->shouldBeExecuted($this->getJobRun($message))) {
            return;
        }

        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateFullParameters(
            $message,
            $jobRun,
            $this->userResolver
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $parentElement = $validatedParameters->getSubject();

        $items = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::ITEMS_TO_DELETE->value
        );
        $totalItems = count($items);
        $stepName = $this->getJobStep($message)->getName();

        foreach ($items as $elementId) {
            $element = $this->getElementById(
                new ElementDescriptor($parentElement->getType(), $elementId),
                $user,
                $this->elementService
            );

            $this->deleteElement($element, $parentElement->getId(), $user);

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

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ITEMS_TO_DELETE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ITEMS_TO_DELETE->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }

    /**
     * @throws Exception
     */
    private function deleteElement(
        ElementInterface $element,
        int $parentId,
        UserInterface $user
    ): void {
        $isParent = $element->getId() === $parentId;

        try {
            match ($isParent) {
                true => $this->elementDeleteService->deleteParentElement($element, $user),
                false => $this->elementDeleteService->deleteElement($element, $user, true),
            };
        } catch (Exception $exception) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_DELETE_FAILED_MESSAGE->value,
                [
                    'type' => $element->getType(),
                    'id' => $element->getId(),
                    'message' => $exception->getMessage(),
                ],
            ));
        }
    }
}
