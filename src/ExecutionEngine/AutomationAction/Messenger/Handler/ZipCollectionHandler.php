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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Handler\AbstractAutomationActionHandler;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\ZipServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class ZipCollectionHandler extends AbstractAutomationActionHandler
{
    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly UserResolverInterface $userResolver
    )
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ZipCollectionMessage $message): void
    {
        $asset = $message->getElement();

        if(!$asset) {
            $this->abortAction(
                'no_asset_found',
                [],
                'studio_backend',
                NotFoundException::class
            );
        }

        $jobRun = $this->getJobRun($message);

        $user = $this->userResolver->getById($jobRun->getOwnerId());

        if(!$user) {
            $this->abortAction(
                'no_user_found',
                [],
                'studio_backend',
                NotFoundException::class
            );
        }

        $asset = $this->assetService->getAssetElement($user, $asset->getId());

        // TODO in GEE get it with offset?
        $context = $jobRun->getContext();

        $assets = $context[ZipServiceInterface::ASSETS_INDEX] ?? [];

        if (in_array($asset->getId(), $assets, true)) {
            return;
        }

        $assets[] = $asset->getId();

        $this->updateJobRunContext($jobRun, ZipServiceInterface::ASSETS_INDEX, $assets);
    }
}
