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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Handler;

use Codeception\Test\Unit;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Handler\CsvCollectionHandler;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\User;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class CsvCollectionHandlerTest extends Unit
{
    private const JOB_RUN_ID = 7;

    private const USER_ID = 3;

    public function testExportWithoutExplicitPaginationCollectsTheCompleteReport(): void
    {
        $jobRun = $this->createJobRun();
        $reportConfig = new Config();
        $csvData = [['id', 'name'], [1, 'first'], [2, 'second']];

        $adapterService = $this->createMock(AdapterServiceInterface::class);
        $adapterService
            ->expects($this->once())
            ->method('getData')
            ->with(
                $reportConfig,
                $this->callback(
                    static fn (ChartDataParameter $parameter): bool => $parameter instanceof ExportParameter
                        && !$parameter->isPaginated()
                )
            )
            ->willReturn(['data' => [['id' => 1, 'name' => 'first'], ['id' => 2, 'name' => 'second']], 'total' => 2]);

        $configService = $this->makeEmpty(CustomReportConfigServiceInterface::class, [
            'getAllowedReportForUser' => $reportConfig,
            'getFieldsForExport' => ['id', 'name'],
            'generateCsvData' => $csvData,
        ]);

        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->expects($this->once())->method('store')->with(self::JOB_RUN_ID, 0, $csvData);

        $handler = $this->createHandler($jobRun, $adapterService, $configService, $exportDataStorageService);

        $handler(new CsvCollectionMessage(self::JOB_RUN_ID, 0));

        $this->assertArrayNotHasKey(StepConfig::GRID_EXPORT_DATA->value, $jobRun->getContext() ?? []);
    }

    private function createJobRun(): JobRun
    {
        $jobRun = new JobRun(self::USER_ID);
        // the id is normally assigned by Doctrine
        new ReflectionProperty(JobRun::class, 'id')->setValue($jobRun, self::JOB_RUN_ID);
        $jobRun->setJob(new Job(Jobs::CREATE_CSV->value, [$this->createJobStep()]));
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);

        return $jobRun;
    }

    private function createJobStep(): JobStep
    {
        return new JobStep(
            'collection',
            CsvCollectionMessage::class,
            '',
            [
                StepConfig::CUSTOM_REPORT_CONFIG->value => [
                    'name' => 'testReport',
                    'filters' => ['columnFilters' => [], 'drillDownFilters' => []],
                    'includeHeaders' => true,
                ],
            ]
        );
    }

    private function createHandler(
        JobRun $jobRun,
        AdapterServiceInterface $adapterService,
        CustomReportConfigServiceInterface $configService,
        ExportDataStorageServiceInterface $exportDataStorageService
    ): CsvCollectionHandler {
        $handler = new CsvCollectionHandler(
            $exportDataStorageService,
            $this->makeEmpty(PublishServiceInterface::class),
            $this->makeEmpty(UserTopicServiceInterface::class, ['getUserTopic' => 'user-topic']),
            $configService,
            $adapterService,
            $this->makeEmpty(UserResolverInterface::class, ['getById' => (new User())->setId(self::USER_ID)])
        );
        $handler->setJobRunRepository($this->makeEmpty(JobRunRepositoryInterface::class, ['update' => $jobRun]));
        $handler->setJobRunExtractor($this->makeEmpty(JobRunExtractorInterface::class, [
            'getJobRun' => $jobRun,
            'getJobStep' => $jobRun->getJob()->getSteps()[0],
        ]));
        $handler->setJobExecutionAgent($this->makeEmpty(JobExecutionAgentInterface::class));
        $handler->setLogger(new NullLogger());
        $handler->setTranslator($this->makeEmpty(TranslatorInterface::class));

        return $handler;
    }
}
