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

namespace Pimcore\Bundle\StudioBackendBundle\Document\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\CloneData;
use Pimcore\Bundle\StudioBackendBundle\Document\ExecutionEngine\AutomationAction\Messenger\Messages\CloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\ElementReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function array_key_exists;

/**
 * @internal
 */
#[AsMessageHandler]
final class CloneHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    private const string LANGUAGE_KEY = 'language';

    private const string ENABLE_INHERITANCE_KEY = 'enableInheritance';

    private const string UPDATE_REFERENCES_KEY = 'updateReferences';

    private const array CLONE_DATA_KEYS = [
        self::LANGUAGE_KEY,
        self::ENABLE_INHERITANCE_KEY,
        self::UPDATE_REFERENCES_KEY,
    ];

    public function __construct(
        private readonly CloneServiceInterface $cloneService,
        private readonly ElementReferenceServiceInterface $elementReferenceService,
        private readonly ElementServiceInterface $elementService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(CloneMessage $message): void
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
                EnvironmentVariables::ORIGINAL_PARENT_ID->value,
                EnvironmentVariables::PARENT_ID->value,
                EnvironmentVariables::CLONE_DATA->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $environmentVariables = $validatedParameters->getEnvironmentData();
        $cloneData = $this->getCloneData($environmentVariables);

        $user = $validatedParameters->getUser();
        $source = $this->getElementById(
            new ElementDescriptor(
                ElementTypes::TYPE_DOCUMENT,
                $this->extractConfigFieldFromJobStepConfig($message, CloneServiceInterface::DOCUMENT_TO_CLONE),
            ),
            $user,
            $this->elementService
        );

        if (!$source instanceof Document) {
            return;
        }

        $parent = $this->cloneService->getNewCloneTarget(
            $user,
            $source,
            $environmentVariables[EnvironmentVariables::ORIGINAL_PARENT_ID->value],
            $environmentVariables[EnvironmentVariables::PARENT_ID->value],
        );

        $newObject = $this->cloneService->cloneDocument($source, $parent, $user, $cloneData);
        $this->updateContextArrayValues(
            $jobRun,
            EnvironmentVariables::REWRITE_CONFIGURATION->value,
            [$source->getId() => $newObject->getId()]
        );

        if ($cloneData->isUpdateReferences() === true &&
            $this->getTotalSteps($jobRun) === ($this->getCurrentStep($jobRun) + 1)
        ) {
            $rewriteConfiguration = $jobRun->getContext()[EnvironmentVariables::REWRITE_CONFIGURATION->value] ?? [];
            $childJobRunId = $this->elementReferenceService->rewriteReferencesWithExecutionEngine(
                $user,
                $rewriteConfiguration,
                array_values($rewriteConfiguration),
                ElementTypes::TYPE_DOCUMENT
            );

            $this->updateJobRunContext($jobRun, JobRunContext::CHILD_JOB_RUN->value, $childJobRunId);
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(CloneServiceInterface::DOCUMENT_TO_CLONE);
        $this->stepConfiguration->setAllowedTypes(CloneServiceInterface::DOCUMENT_TO_CLONE, 'int');
    }

    /**
     * @throws Exception
     */
    private function getCloneData(array $environmentVariables): CloneData
    {
        $cloneData = $environmentVariables[EnvironmentVariables::CLONE_DATA->value];
        foreach (self::CLONE_DATA_KEYS as $key) {
            if (array_key_exists($key, $cloneData) === false) {
                $this->abort(
                    $this->getAbortData(
                        Config::ENVIRONMENT_VARIABLE_NOT_FOUND->value,
                        ['variable' => $key]
                    )
                );
            }
        }

        return new CloneData(
            $cloneData[self::LANGUAGE_KEY],
            $cloneData[self::ENABLE_INHERITANCE_KEY],
            $cloneData[self::UPDATE_REFERENCES_KEY]
        );
    }
}
