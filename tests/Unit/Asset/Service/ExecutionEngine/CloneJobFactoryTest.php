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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service\ExecutionEngine;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\FolderCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model\CloneDescendants;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CloneJobFactory;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final class CloneJobFactoryTest extends Unit
{
    private const int ORIGINAL_PARENT_ID = 10;

    private const int NEW_PARENT_ID = 20;

    public function testFoldersAreClonedInOneStepBeforeTheAssetsAreClonedInParallel(): void
    {
        $job = (new CloneJobFactory())->create(
            new CloneDescendants([11, 15, 12], [13, 14, 16]),
            self::ORIGINAL_PARENT_ID,
            self::NEW_PARENT_ID
        );

        $this->assertInstanceOf(Job::class, $job);
        $this->assertSame(Jobs::CLONE_ASSETS->value, $job->getName());

        [$folderStep, $assetStep] = $job->getSteps();
        $this->assertCount(2, $job->getSteps());

        $this->assertSame(JobSteps::ASSET_FOLDER_CLONING->value, $folderStep->getName());
        $this->assertSame(FolderCloneMessage::class, $folderStep->getMessageFQCN());
        $this->assertSame(SelectionProcessingMode::ONCE, $folderStep->getSelectionProcessingMode());
        // The folders are cloned in exactly the order they are given in, parents before their children
        $this->assertSame([11, 15, 12], $folderStep->getConfig()[StepConfig::FOLDERS_TO_CLONE->value]);

        $this->assertSame(JobSteps::ASSET_CLONING->value, $assetStep->getName());
        $this->assertSame(AssetCloneMessage::class, $assetStep->getMessageFQCN());
        $this->assertSame(SelectionProcessingMode::FOR_EACH, $assetStep->getSelectionProcessingMode());

        // Only the assets are processed in parallel; their target folders exist once the first step is done
        $this->assertSame([13, 14, 16], $this->getSelectedIds($job));
        $this->assertSame(
            [
                EnvironmentVariables::ORIGINAL_PARENT_ID->value => self::ORIGINAL_PARENT_ID,
                EnvironmentVariables::PARENT_ID->value => self::NEW_PARENT_ID,
            ],
            $job->getEnvironmentData()
        );
    }

    public function testOnlyFolders(): void
    {
        $job = (new CloneJobFactory())->create(
            new CloneDescendants([11, 12], []),
            self::ORIGINAL_PARENT_ID,
            self::NEW_PARENT_ID
        );

        $this->assertInstanceOf(Job::class, $job);
        $this->assertCount(1, $job->getSteps());
        $this->assertSame(FolderCloneMessage::class, $job->getSteps()[0]->getMessageFQCN());
        // An empty for-each step would still be dispatched once, without an element
        $this->assertSame([], $job->getSelectedElements());
    }

    public function testOnlyAssets(): void
    {
        $job = (new CloneJobFactory())->create(
            new CloneDescendants([], [13, 14]),
            self::ORIGINAL_PARENT_ID,
            self::NEW_PARENT_ID
        );

        $this->assertInstanceOf(Job::class, $job);
        $this->assertCount(1, $job->getSteps());
        $this->assertSame(AssetCloneMessage::class, $job->getSteps()[0]->getMessageFQCN());
        $this->assertSame([13, 14], $this->getSelectedIds($job));
    }

    public function testNothingToClone(): void
    {
        $this->assertNull(
            (new CloneJobFactory())->create(
                new CloneDescendants([], []),
                self::ORIGINAL_PARENT_ID,
                self::NEW_PARENT_ID
            )
        );
    }

    /**
     * @return int[]
     */
    private function getSelectedIds(Job $job): array
    {
        return array_map(
            static fn (ElementDescriptor $element): int => $element->getId(),
            $job->getSelectedElements()
        );
    }
}
