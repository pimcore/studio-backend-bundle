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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Pimcore\Model\Asset;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class AssetCloneHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementServiceInterface $elementService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly CloneServiceInterface $cloneService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(AssetCloneMessage $message): void
    {
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
        $assetElement = $validatedParameters->getSubject();
        $environmentVariables = $validatedParameters->getEnvironmentData();
        $source = $this->getElementById(
            $assetElement,
            $user,
            $this->elementService
        );
        if (!$source instanceof Asset) {
            return;
        }

        $parent = $this->cloneService->getNewCloneTarget(
            $user,
            $source,
            $environmentVariables[EnvironmentVariables::ORIGINAL_PARENT_ID->value],
            $environmentVariables[EnvironmentVariables::PARENT_ID->value],
        );

        $this->cloneService->cloneElement($source, $parent, $user);

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
