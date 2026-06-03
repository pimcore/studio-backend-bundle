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
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RewriteRefMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\ElementReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class RewriteRefHandler extends AbstractHandler
{
    use ElementProviderTrait;
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementReferenceServiceInterface $elementReferenceService,
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
    public function __invoke(RewriteRefMessage $message): void
    {
        if (!$this->shouldBeExecuted($this->getJobRun($message))) {
            return;
        }

        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                EnvironmentVariables::REWRITE_CONFIGURATION->value,
                EnvironmentVariables::REWRITE_PARAMETERS->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $environmentVariables = $validatedParameters->getEnvironmentData();
        $rewriteConfiguration = $environmentVariables[EnvironmentVariables::REWRITE_CONFIGURATION->value];
        $rewriteParameters = $environmentVariables[EnvironmentVariables::REWRITE_PARAMETERS->value];

        $elementIds = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::ELEMENTS_TO_REWRITE_REFERENCES->value
        );
        $elementType = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::ELEMENT_TYPE_TO_REWRITE_REFERENCES->value
        );
        $totalItems = count($elementIds);
        $stepName = $this->getJobStep($message)->getName();

        foreach ($elementIds as $elementId) {
            $element = $this->getElementById(
                new ElementDescriptor($elementType, $elementId),
                $user,
                $this->elementService,
            );

            try {
                $this->elementReferenceService->rewriteElementReferences(
                    $user,
                    $element,
                    $rewriteConfiguration,
                    $rewriteParameters,
                );
            } catch (Exception $exception) {
                $this->abort($this->getAbortData(
                    Config::ELEMENT_REWRITE_REFERENCES_FAILED_MESSAGE->value,
                    [
                        'type' => $element->getType(),
                        'id' => $element->getId(),
                        'message' => $exception->getMessage(),
                    ],
                ));
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

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ELEMENTS_TO_REWRITE_REFERENCES->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENTS_TO_REWRITE_REFERENCES->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );

        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_TYPE_TO_REWRITE_REFERENCES->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_TYPE_TO_REWRITE_REFERENCES->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
    }
}
