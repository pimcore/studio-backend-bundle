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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Traits;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConsoleDependencyMissingException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\ExecuteActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;

/**
 * @internal
 */
trait HandlerValidationTrait
{
    /**
     * @throws ConsoleDependencyMissingException
     */
    private function validateJobParameters(
        GenericExecutionEngineMessageInterface $message,
        JobRun $jobRun,
        UserResolverInterface $userResolver,
        ?array $requiredEnvironmentVariables = null,
    ): AbortActionData|ExecuteActionData
    {
        $element = $message->getElement();
        if (!$element) {
            return $this->getAbortParameters(Config::ASSET_NOT_FOUND_MESSAGE->value);
        }

        $user = $userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            return $this->getAbortParameters(
                Config::USER_NOT_FOUND_MESSAGE->value,
                ['userId' => $jobRun->getOwnerId()
                ]
            );
        }

        $jobEnvironmentData = [];
        if ($requiredEnvironmentVariables !== null) {
            $jobEnvironmentData = $jobRun->getJob()?->getEnvironmentData();
            foreach ($requiredEnvironmentVariables as $requiredEnvironmentVariable) {
                if (!isset($jobEnvironmentData[$requiredEnvironmentVariable])) {
                    return $this->getAbortParameters(
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

    private function getAbortParameters(string $message, array $messageParams = []): AbortActionData
    {
        return new AbortActionData(
            $message,
            $messageParams,
        );
    }
}
