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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConsoleDependencyMissingException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\ExecuteActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
class AbstractHandler extends AbstractAutomationActionHandler
{
    /**
     * @throws ConsoleDependencyMissingException
     */
    protected function validateJobParameters(
        GenericExecutionEngineMessageInterface $message,
        JobRun $jobRun,
        UserResolverInterface $userResolver,
        ?array $requiredEnvironmentVariables = null,
    ): AbortActionData|ExecuteActionData {
        $element = $message->getElement();
        if (!$element) {
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

        return new ExecuteActionData(
            $user,
            $element,
            $jobEnvironmentData
        );
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
            TranslatorService::DOMAIN,
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
}
