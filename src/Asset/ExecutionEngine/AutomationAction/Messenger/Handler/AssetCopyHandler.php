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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCopyMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Traits\HandlerValidationTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\CloneEnvironmentVariables;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class AssetCopyHandler extends AbstractAutomationActionHandler
{
    use HandlerValidationTrait;

    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly UserResolverInterface $userResolver,
        private readonly CloneServiceInterface $cloneService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(AssetCopyMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                CloneEnvironmentVariables::ORIGINAL_PARENT_ID->value,
                CloneEnvironmentVariables::PARENT_ID->value,
            ],
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abortAction(
                $validatedParameters->getTranslationKey(),
                $validatedParameters->getTranslationParameters(),
                Config::CONTEXT->value,
                $validatedParameters->getExceptionClassName()
            );
        }

        $user = $validatedParameters->getUser();
        $assetElement = $validatedParameters->getSubject();
        $environmentVariables = $validatedParameters->getEnvironmentData();
        $source = $this->assetService->getAssetElement($user, $assetElement->getId());
        $parent = $this->cloneService->getNewCloneTarget(
            $user,
            $source,
            $environmentVariables[CloneEnvironmentVariables::ORIGINAL_PARENT_ID->value],
            $environmentVariables[CloneEnvironmentVariables::PARENT_ID->value],
        );

        // TODO Send SSE for percentage update
        $this->cloneService->cloneElement(
            $source,
            $parent,
            $user
        );
    }
}
