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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\ExecuteActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\FullExecuteActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
class AbstractHandler extends AbstractAutomationActionHandler
{
    protected function shouldBeExecuted(JobRun $jobRun): bool
    {
        return $jobRun->getState() === JobRunStates::RUNNING;
    }

    /**
     * @throws Exception
     */
    protected function validateFullParameters(
        GenericExecutionEngineMessageInterface $message,
        JobRun $jobRun,
        UserResolverInterface $userResolver,
        ?array $requiredEnvironmentVariables = null,
    ): AbortActionData|FullExecuteActionData {
        $parameters = $this->validateJobParameters($message, $jobRun, $userResolver, $requiredEnvironmentVariables);
        if ($parameters instanceof FullExecuteActionData || $parameters instanceof AbortActionData) {
            return $parameters;
        }

        return $this->getAbortData(Config::NO_ELEMENT_PROVIDED->value);
    }

    protected function validateJobParameters(
        GenericExecutionEngineMessageInterface $message,
        JobRun $jobRun,
        UserResolverInterface $userResolver,
        ?array $requiredEnvironmentVariables = null,
    ): AbortActionData|FullExecuteActionData|ExecuteActionData {
        $hasElements = $jobRun->getTotalElements() > 0;
        $element = $message->getElement();
        if ($hasElements && !$element) {
            return $this->getAbortData(Config::NO_ELEMENT_PROVIDED->value);
        }

        $user = $userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            return $this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                [
                    'userId' => $jobRun->getOwnerId(),
                ]
            );
        }

        $jobEnvironmentData = [];
        if ($requiredEnvironmentVariables !== null) {
            $jobEnvironmentData = $jobRun->getJob()?->getEnvironmentData();
            foreach ($requiredEnvironmentVariables as $requiredEnvironmentVariable) {
                if (!isset($jobEnvironmentData[$requiredEnvironmentVariable])) {
                    return $this->getAbortData(
                        Config::ENVIRONMENT_VARIABLE_NOT_FOUND->value,
                        ['variable' => $requiredEnvironmentVariable]
                    );
                }
            }
        }

        return $this->getExecutionActionData($user, $jobEnvironmentData, $element);
    }

    protected function getAbortData(string $message, array $messageParams = []): AbortActionData
    {
        return new AbortActionData(
            $message,
            $messageParams,
        );
    }

    /**
     * @throws Exception
     */
    protected function abort(AbortActionData $abortActionData): void
    {
        $this->abortAction(
            $abortActionData->getTranslationKey(),
            $abortActionData->getTranslationParameters(),
            TranslatorServiceInterface::DOMAIN,
            $abortActionData->getExceptionClassName()
        );
    }

    /**
     * @throws Exception
     */
    protected function getElementById(
        ElementDescriptor $jobElement,
        UserInterface $user,
        ElementServiceInterface $elementService
    ): ElementInterface {
        try {
            return $elementService->getAllowedElementById(
                $jobElement->getType(),
                $jobElement->getId(),
                $user
            );
        } catch (AccessDeniedException) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_PERMISSION_MISSING_MESSAGE->value,
                [
                    'userId' => $user->getId(),
                    'permission' => ElementPermissions::VIEW_PERMISSION,
                    'type' => ucfirst($jobElement->getType()),
                    'id' => $jobElement->getId(),
                ],
            ));
        } catch (NotFoundException) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_NOT_FOUND_MESSAGE->value,
                [
                    'id' => $jobElement->getId(),
                    'type' => ucfirst($jobElement->getType()),
                ],
            ));
        }

        throw new EnvironmentException('How did I get here?');
    }

    protected function updateContextArrayValues(JobRun $jobRun, string $key, array $value): void
    {
        $context = $jobRun->getContext();
        $contextValue = $value;

        if (isset($context[$key])) {
            $contextValue = $context[$key];
            $contextValue[key($value)] = reset($value);
        }

        $this->updateJobRunContext($jobRun, $key, $contextValue);
    }

    protected function updateJobRunContextValues(
        JobRun $jobRun,
        array $values,
        bool $persist = true
    ): void {
        $jobRun->setContext(
            array_merge(
                $jobRun->getContext() ?? [],
                $values
            )
        );
        if($persist) {
            $this->jobRunRepository->update($jobRun);
        }
    }

    private function getExecutionActionData(
        UserInterface $user,
        array $jobEnvironmentData,
        ?ElementDescriptor $element
    ): ExecuteActionData|FullExecuteActionData {
        if ($element === null) {
            return new ExecuteActionData($user, $jobEnvironmentData);
        }

        return new FullExecuteActionData($element, $user, $jobEnvironmentData);
    }
}
