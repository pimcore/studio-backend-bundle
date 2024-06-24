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
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipCollectionHandler extends AbstractHandler
{
    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly UserResolverInterface $userResolver
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
            $this->abortAction(
                $validatedParameters->getTranslationKey(),
                $validatedParameters->getTranslationParameters(),
                Config::CONTEXT->value,
                $validatedParameters->getExceptionClassName()
            );
        }

        $user = $validatedParameters->getUser();
        $asset = $validatedParameters->getSubject();

        try {
            $asset = $this->assetService->getAssetElement($user, $asset->getId());
        } catch (NotFoundException) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_NOT_FOUND_MESSAGE->value,
                [
                    'id' => $asset->getId(),
                    'type' => ucfirst($asset->getType()),
                ],
            ));
        }

        $context = $jobRun->getContext();

        $assets = $context[ZipServiceInterface::ASSETS_INDEX] ?? [];

        if (in_array($asset->getId(), $assets, true)) {
            return;
        }

        $assets[] = $asset->getId();

        $this->updateJobRunContext($jobRun, ZipServiceInterface::ASSETS_INDEX, $assets);
        // TODO Send SSE for percentage update
    }
}
