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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler\FolderCloneHandler;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\FolderCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine\Progress;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use ReflectionProperty;

/**
 * @internal
 */
final class FolderCloneHandlerTest extends Unit
{
    private const int ORIGINAL_PARENT_ID = 10;

    private const int NEW_PARENT_ID = 20;

    /**
     * @throws Exception
     */
    public function testClonesEveryFolderIntoItsTargetInTheGivenOrder(): void
    {
        $calls = [];
        $user = new User();

        $cloneService = $this->createMock(CloneServiceInterface::class);
        $cloneService->method('getNewCloneTarget')->willReturnCallback(
            function (
                UserInterface $currentUser,
                Asset $source,
                int $originalParentId,
                int $parentId
            ) use (&$calls, $user): Asset {
                $this->assertSame($user, $currentUser);
                $this->assertSame(self::ORIGINAL_PARENT_ID, $originalParentId);
                $this->assertSame(self::NEW_PARENT_ID, $parentId);
                $calls[] = 'target:' . $source->getId();

                return $this->makeFolder(100 + $source->getId());
            }
        );
        $cloneService->method('cloneElement')->willReturnCallback(
            function (Asset $source, Asset $parent, UserInterface $currentUser) use (&$calls, $user): Asset {
                $this->assertSame($user, $currentUser);
                $calls[] = 'clone:' . $source->getId() . '->' . $parent->getId();

                return $this->makeFolder(200 + $source->getId());
            }
        );

        $this->createHandler($cloneService, $user, [15, 11, 12])(new FolderCloneMessage(1, 0));

        // Every folder is created before the next one is looked up, so a sub folder always finds its parent
        $this->assertSame(
            [
                'target:15', 'clone:15->115',
                'target:11', 'clone:11->111',
                'target:12', 'clone:12->112',
            ],
            $calls
        );
    }

    /**
     * @throws Exception
     */
    public function testReportsTheProgressOfTheFoldersNotOfTheSelectedAssets(): void
    {
        $cloneService = $this->createMock(CloneServiceInterface::class);
        $cloneService->method('getNewCloneTarget')->willReturn($this->makeFolder(100));
        $cloneService->method('cloneElement')->willReturn($this->makeFolder(200));

        $published = [];
        $publishService = $this->createMock(PublishServiceInterface::class);
        $publishService->method('publish')->willReturnCallback(
            static function (string $topic, Progress $progress) use (&$published): void {
                $published[] = [$progress->getProgress(), $progress->getCurrentStep(), $progress->getTotalSteps()];
            }
        );

        // 245 assets are selected for the following step, the folder step itself has 3 folders
        $this->createHandler($cloneService, new User(), [15, 11, 12], $publishService)(new FolderCloneMessage(1, 0));

        $this->assertSame([[33, 1, 2], [66, 1, 2], [100, 1, 2]], $published);
    }

    /**
     * @param int[] $folderIds
     *
     * @throws Exception
     */
    private function createHandler(
        CloneServiceInterface $cloneService,
        User $user,
        array $folderIds,
        ?PublishServiceInterface $publishService = null
    ): FolderCloneHandler {
        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn($user);

        $elementService = $this->createMock(ElementServiceInterface::class);
        $elementService->method('getAllowedElementById')->willReturnCallback(
            fn (string $type, int $id): Folder => $this->makeFolder($id)
        );

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        $handler = new FolderCloneHandler(
            $elementService,
            $publishService ?? $this->createMock(PublishServiceInterface::class),
            $userResolver,
            $userTopicService,
            $cloneService
        );
        $handler->setJobRunRepository($this->createMock(JobRunRepositoryInterface::class));
        $handler->setJobRunExtractor($this->createJobRunExtractor($folderIds));

        return $handler;
    }

    /**
     * @param int[] $folderIds
     */
    private function createJobRunExtractor(array $folderIds): JobRunExtractorInterface
    {
        $jobRun = new JobRun(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setTotalElements(245);
        $jobRun->setJob(new Job(
            name: 'job',
            steps: [
                new JobStep('step', FolderCloneMessage::class, '', []),
                new JobStep('assets', AssetCloneMessage::class, '', []),
            ],
            environmentData: [
                EnvironmentVariables::ORIGINAL_PARENT_ID->value => self::ORIGINAL_PARENT_ID,
                EnvironmentVariables::PARENT_ID->value => self::NEW_PARENT_ID,
            ]
        ));
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        $jobStep = $this->createMock(JobStepInterface::class);
        $jobStep->method('getName')->willReturn('step');
        $jobStep->method('getConfig')->willReturn([StepConfig::FOLDERS_TO_CLONE->value => $folderIds]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);
        $extractor->method('getJobStep')->willReturn($jobStep);

        return $extractor;
    }

    /**
     * @throws Exception
     */
    private function makeFolder(int $id): Folder
    {
        return $this->makeEmpty(Folder::class, ['getId' => $id]);
    }
}
