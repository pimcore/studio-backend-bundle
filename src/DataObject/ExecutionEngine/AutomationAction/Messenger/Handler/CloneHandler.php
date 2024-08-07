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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\CloneMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\ElementReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\JobRunContext;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementDescriptor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CloneHandler extends AbstractHandler
{
    use HandlerProgressTrait;

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
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $environmentVariables = $validatedParameters->getEnvironmentData();
        $source = $this->getElementById(
            new ElementDescriptor(
                ElementTypes::TYPE_OBJECT,
                $this->extractConfigFieldFromJobStepConfig($message, CloneServiceInterface::OBJECT_TO_CLONE)
            ),
            $user,
            $this->elementService
        );
        if (!$source instanceof DataObject) {
            return;
        }

        $parent = $this->cloneService->getNewCloneTarget(
            $user,
            $source,
            $environmentVariables[EnvironmentVariables::ORIGINAL_PARENT_ID->value],
            $environmentVariables[EnvironmentVariables::PARENT_ID->value],
        );

        $newObject = $this->cloneService->cloneDataObject($source, $parent, $user);

        if ($newObject instanceof Concrete) {
            $this->updateContextArrayValues(
                $jobRun,
                EnvironmentVariables::REWRITE_CONFIGURATION->value,
                [$source->getId() => $newObject->getId()]
            );
        }

        if ($environmentVariables[EnvironmentVariables::UPDATE_REFERENCES->value] === true &&
            $this->getTotalSteps($jobRun) === ($this->getCurrentStep($jobRun) + 1)
        ) {
            $rewriteConfiguration = $jobRun->getContext()[EnvironmentVariables::REWRITE_CONFIGURATION->value] ?? [];
            $childJobRunId = $this->elementReferenceService->rewriteReferencesWithExecutionEngine(
                $user,
                $rewriteConfiguration,
                array_values($rewriteConfiguration),
                ElementTypes::TYPE_OBJECT
            );

            $this->updateJobRunContext($jobRun, JobRunContext::CHILD_JOB_RUN->value, $childJobRunId);
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(CloneServiceInterface::OBJECT_TO_CLONE);
        $this->stepConfiguration->setAllowedTypes(CloneServiceInterface::OBJECT_TO_CLONE, 'int');
    }
}
