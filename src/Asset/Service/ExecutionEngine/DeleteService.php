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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DeleteService implements DeleteServiceInterface
{
    public const ASSET_TO_DELETE = 'asset_to_delete';

    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private ElementDeleteServiceInterface $elementDeleteService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private int $recycleBinThreshold
    ) {
    }

    /**
     * @throws ElementDeletionFailedException|EnvironmentException|ForbiddenException|InvalidElementTypeException
     */
    public function deleteAssets(
        Asset $asset,
        UserInterface $user
    ): ?int {
        if (!$asset->hasChildren()) {
            $this->elementDeleteService->addElementToRecycleBin($asset, $user);
            $this->elementDeleteService->deleteParentElement($asset, $user);

            return null;
        }

        return $this->deleteAssetsWithExecutionEngine($asset, $user);
    }

    private function deleteAssetsWithExecutionEngine(
        Asset $asset,
        UserInterface $user
    ): int {
        $ids = $this->assetSearchService->getChildrenIds($asset->getRealFullPath(), 'desc');
        // ToDo This might need to be reconsidered for separate job in the future
        if (count($ids) < $this->recycleBinThreshold) {
            $this->elementDeleteService->addElementToRecycleBin($asset, $user);
        }

        $jobSteps = array_map(
            static fn (int $id) => new JobStep(
                JobSteps::ASSET_DELETION->value,
                AssetDeleteMessage::class,
                '',
                [self::ASSET_TO_DELETE => $id]
            ),
            $ids
        );

        $jobSteps[] = new JobStep(
            JobSteps::ASSET_DELETION->value,
            AssetDeleteMessage::class,
            '',
            [self::ASSET_TO_DELETE => $asset->getId()]
        );

        $job = new Job(
            name: Jobs::DELETE_ASSETS->value,
            steps: $jobSteps,
            selectedElements:[
                new ElementDescriptor(
                    ElementTypes::TYPE_ASSET,
                    $asset->getId()
                ),
            ]
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution($job, $user->getId(), Config::CONTEXT->value);

        return $jobRun->getId();
    }
}
