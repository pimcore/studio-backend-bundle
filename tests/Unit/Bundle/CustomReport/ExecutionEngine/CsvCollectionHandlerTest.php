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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\CustomReport\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Handler\CsvCollectionHandler;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\User;
use ReflectionProperty;

/**
 * @internal
 */
final class CsvCollectionHandlerTest extends Unit
{
    private const array ROWS = [['id', 'name'], [1, 'first'], [2, 'second']];

    /**
     * @throws Exception
     */
    public function testStoresTheReportRowsOutsideOfTheJobRun(): void
    {
        if (!class_exists(Config::class)) {
            $this->markTestSkipped('The custom reports bundle is not installed.');
        }

        $jobRun = $this->createJobRun();

        $repository = $this->createMock(JobRunRepositoryInterface::class);
        // Only the progress update of the step writes the job run, not the rows
        $repository->expects($this->atMost(1))->method('update');

        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->expects($this->once())->method('store')->with(1, 0, self::ROWS);

        $this->createHandler($repository, $jobRun, $exportDataStorageService)(new CsvCollectionMessage(1, 0));

        $this->assertArrayNotHasKey(StepConfig::GRID_EXPORT_DATA->value, $jobRun->getContext() ?? []);
    }

    private function createHandler(
        JobRunRepositoryInterface $repository,
        JobRun $jobRun,
        ExportDataStorageServiceInterface $exportDataStorageService
    ): CsvCollectionHandler {
        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn(new User());

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        $reportConfigService = $this->createMock(CustomReportConfigServiceInterface::class);
        $reportConfigService->method('getAllowedReportForUser')->willReturn($this->createMock(Config::class));
        $reportConfigService->method('getFieldsForExport')->willReturn([]);
        $reportConfigService->method('generateCsvData')->willReturn(self::ROWS);

        $adapterService = $this->createMock(AdapterServiceInterface::class);
        $adapterService->method('getData')->willReturn(['data' => []]);

        $handler = new CsvCollectionHandler(
            $exportDataStorageService,
            $this->createMock(PublishServiceInterface::class),
            $userTopicService,
            $reportConfigService,
            $adapterService,
            $userResolver
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
            StepConfig::CUSTOM_REPORT_CONFIG->value => [
                'name' => 'report',
                'filters' => ['columnFilters' => [], 'drillDownFilters' => []],
            ],
        ]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);
        $extractor->method('getJobStep')->willReturn($jobStep);

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
