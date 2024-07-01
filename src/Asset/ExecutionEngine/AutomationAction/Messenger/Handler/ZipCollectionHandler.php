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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\HandlerProgressTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function in_array;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ElementServiceInterface $elementService,
        private readonly UserResolverInterface $userResolver,
        private readonly PublishServiceInterface $publishService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipCollectionMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $jobAsset = $validatedParameters->getSubject();
        $this->getElementById(
            $jobAsset,
            $user,
            $this->elementService
        );

        $context = $jobRun->getContext();

        $assets = $context[ZipServiceInterface::ASSETS_INDEX] ?? [];

        if (in_array($jobAsset->getId(), $assets, true)) {
            return;
        }

        $assets[] = $jobAsset->getId();

        $this->updateJobRunContext($jobRun, ZipServiceInterface::ASSETS_INDEX, $assets);

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
