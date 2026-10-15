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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Handler\ExportDataCollectionHandler;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementContextServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\User;
use ReflectionProperty;

/**
 * @internal
 */
final class ExportDataCollectionHandlerTest extends Unit
{
    private const int BATCH_SIZE = 500;

    /**
     * @throws Exception
     */
    public function testDoesNotLoadOrWriteTheJobRunPerExportedObject(): void
    {
        $jobRun = $this->createJobRun();
        // Rows collected by earlier steps are part of the job run context and must not be rewritten per object
        $jobRun->setContext([StepConfig::GRID_EXPORT_DATA->value => [1 => ['title' => 'previous']]]);

        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->expects($this->atMost(2))->method('update');

        $this->createHandler($repository, $jobRun)(new ExportDataCollectionMessage(1, 0));

        $this->assertCount(
            self::BATCH_SIZE + 1,
            $jobRun->getContext()[StepConfig::GRID_EXPORT_DATA->value]
        );
    }

    private function createHandler(
        JobRunRepositoryInterface $repository,
        JobRun $jobRun
    ): ExportDataCollectionHandler {
        $user = new User();
        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn($user);

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        $gridService = $this->createMock(GridServiceInterface::class);
        $gridService->method('getConfigurationForExport')->willReturn(new ColumnCollection([]));
        $gridService->method('getGridValuesForElement')->willReturn(['title' => 'value']);

        $columnConfigurationService = $this->createMock(ColumnConfigurationServiceInterface::class);
        $columnConfigurationService->method('getAvailableDataObjectColumnConfiguration')->willReturn([]);

        $elementContextService = $this->createMock(ElementContextServiceInterface::class);
        $elementContextService->method('runWithUnpublishedElements')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $handler = new ExportDataCollectionHandler(
            $columnConfigurationService,
            $this->createMock(PublishServiceInterface::class),
            $userTopicService,
            $userResolver,
            $gridService,
            $elementContextService
        );
        $handler->setJobRunRepository($repository);
        $handler->setJobRunExtractor($this->createJobRunExtractor($jobRun));

        return $handler;
    }

    private function createJobRunExtractor(JobRun $jobRun): JobRunExtractorInterface
    {
        $jobStep = $this->createMock(JobStepInterface::class);
        $jobStep->method('getName')->willReturn('step');
        $jobStep->method('getConfig')->willReturn([
            StepConfig::ELEMENTS_TO_EXPORT->value => array_map(
                static fn (int $id): array => ['type' => 'object', 'id' => $id, 'cacheKey' => 'object_' . $id],
                range(100, 99 + self::BATCH_SIZE)
            ),
            StepConfig::ELEMENT_CLASS_ID->value => 'CAR',
            StepConfig::CONFIG_COLUMNS->value => [],
        ]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        // Every extra load of the job run reads its whole context, so it must not happen per element or per value
        $extractor->expects($this->atMost(20))->method('getJobRun')->willReturn($jobRun);
        // Resolving the step loads the whole job run row, so it must not happen once per exported object
        $extractor->expects($this->atMost(10))->method('getJobStep')->willReturn($jobStep);

        return $extractor;
    }

    private function createJobRun(): JobRun
    {
        $jobRun = new JobRun(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        return $jobRun;
    }
}
