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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\FolderCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model\CloneDescendants;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * The folders are cloned first, in one message and in order, because every cloned asset or folder looks up its target
 * by path. Only then are the assets cloned, one message each, so that several workers can process them in parallel.
 *
 * @internal
 */
final readonly class CloneJobFactory implements CloneJobFactoryInterface
{
    public function create(CloneDescendants $descendants, int $originalParentId, int $parentId): ?Job
    {
        $steps = [];
        if ($descendants->getFolderIds() !== []) {
            $steps[] = new JobStep(
                JobSteps::ASSET_FOLDER_CLONING->value,
                FolderCloneMessage::class,
                '',
                [StepConfig::FOLDERS_TO_CLONE->value => $descendants->getFolderIds()],
                SelectionProcessingMode::ONCE
            );
        }

        // A for-each step without elements would still be dispatched once, so it is only added for assets
        if ($descendants->getAssetIds() !== []) {
            $steps[] = new JobStep(JobSteps::ASSET_CLONING->value, AssetCloneMessage::class, '', []);
        }

        if ($steps === []) {
            return null;
        }

        return new Job(
            name: Jobs::CLONE_ASSETS->value,
            steps: $steps,
            selectedElements: array_map(
                static fn (int $id) => new ElementDescriptor(ElementTypes::TYPE_ASSET, $id),
                $descendants->getAssetIds()
            ),
            environmentData: [
                EnvironmentVariables::ORIGINAL_PARENT_ID->value => $originalParentId,
                EnvironmentVariables::PARENT_ID->value => $parentId,
            ]
        );
    }
}
