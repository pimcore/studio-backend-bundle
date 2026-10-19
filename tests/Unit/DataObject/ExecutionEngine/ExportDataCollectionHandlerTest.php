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
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
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
    public function testStoresTheCollectedRowsOutsideOfTheJobRun(): void
    {
        $jobRun = $this->createJobRun();

        // The job run is loaded and written again by every step, so it must not be written for the rows at all
        $repository = $this->createMock(JobRunRepositoryInterface::class);
        $repository->expects($this->never())->method('update');

        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->expects($this->once())
            ->method('store')
            ->with(
                1,
                3,
                $this->callback(
                    static fn (array $rows): bool => array_keys($rows) === range(100, 99 + self::BATCH_SIZE)
                        && $rows[100] === ['title' => 'value']
                )
            );

        $this->createHandler($repository, $jobRun, $exportDataStorageService)(
            new ExportDataCollectionMessage(1, 3)
        );

        $this->assertArrayNotHasKey(StepConfig::GRID_EXPORT_DATA->value, $jobRun->getContext() ?? []);
    }

    private function createHandler(
        JobRunRepositoryInterface $repository,
        JobRun $jobRun,
        ExportDataStorageServiceInterface $exportDataStorageService
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

        $handler = new ExportDataCollectionHandler(
            $columnConfigurationService,
            $exportDataStorageService,
            $this->createMock(PublishServiceInterface::class),
            $userTopicService,
            $userResolver,
            $gridService
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
