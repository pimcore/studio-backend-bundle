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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Export\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Handler\XlsxCreationHandler;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\XlsxCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\Model\GridExportData;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\User;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class XlsxCreationHandlerTest extends Unit
{
    private const array ROWS = [['row-1'], ['row-2']];

    /**
     * @throws Exception
     */
    public function testCreatesTheFileFromTheStoredRowsAndRemovesThem(): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->method('load')->with(1)->willReturn(self::ROWS);
        $exportDataStorageService->expects($this->once())->method('cleanUp')->with(1);

        $exportedRows = null;
        $exportService = $this->createMock(ExportServiceInterface::class);
        $exportService->expects($this->once())->method('createExportFile')->willReturnCallback(
            static function (int $id, GridExportData $data) use (&$exportedRows): void {
                $exportedRows = $data->getExportData();
            }
        );

        $this->createHandler($exportDataStorageService, $exportService, [])(new XlsxCreationMessage(1, 1));

        $this->assertSame(self::ROWS, $exportedRows);
    }

    /**
     * @throws Exception
     */
    public function testUsesRowsOfJobRunsThatStartedWithRowsInTheContext(): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->method('load')->willReturn(null);

        $exportedRows = null;
        $exportService = $this->createMock(ExportServiceInterface::class);
        $exportService->expects($this->once())->method('createExportFile')->willReturnCallback(
            static function (int $id, GridExportData $data) use (&$exportedRows): void {
                $exportedRows = $data->getExportData();
            }
        );

        $this->createHandler(
            $exportDataStorageService,
            $exportService,
            [StepConfig::GRID_EXPORT_DATA->value => self::ROWS]
        )(new XlsxCreationMessage(1, 1));

        $this->assertSame(self::ROWS, $exportedRows);
    }

    public function testAbortsWithoutCreatingAFileIfThereAreNoRows(): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->method('load')->willReturn(null);
        $exportDataStorageService->expects($this->never())->method('cleanUp');

        $exportService = $this->createMock(ExportServiceInterface::class);
        $exportService->expects($this->never())->method('createExportFile');

        $this->expectException(Exception::class);

        $this->createHandler($exportDataStorageService, $exportService, [])(new XlsxCreationMessage(1, 1));
    }

    public function testAbortsWithoutCreatingAFileIfTheStoredRowsCannotBeRead(): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->method('load')->willThrowException(new EnvironmentException('unreadable'));
        $exportDataStorageService->expects($this->never())->method('cleanUp');

        $exportService = $this->createMock(ExportServiceInterface::class);
        $exportService->expects($this->never())->method('createExportFile');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('unreadable');

        $this->createHandler($exportDataStorageService, $exportService, [])(new XlsxCreationMessage(1, 1));
    }

    public function testKeepsTheStoredRowsIfTheFileCannotBeCreated(): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->method('load')->willReturn(self::ROWS);
        // The failed job run is cleaned up by its state change, a retried step still needs the rows
        $exportDataStorageService->expects($this->never())->method('cleanUp');

        $exportService = $this->createMock(ExportServiceInterface::class);
        $exportService->method('createExportFile')->willThrowException(new EnvironmentException('disk full'));

        $this->expectException(Exception::class);

        $this->createHandler($exportDataStorageService, $exportService, [])(new XlsxCreationMessage(1, 1));
    }

    private function createHandler(
        ExportDataStorageServiceInterface $exportDataStorageService,
        ExportServiceInterface $exportService,
        array $context
    ): XlsxCreationHandler {
        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn(new User());

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $key, array $parameters = []): string => $key . json_encode($parameters)
        );

        $handler = new XlsxCreationHandler(
            $exportDataStorageService,
            $this->createMock(PublishServiceInterface::class),
            $userResolver,
            $exportService,
            $userTopicService
        );
        $handler->setJobRunRepository($this->createMock(JobRunRepositoryInterface::class));
        $handler->setJobRunExtractor($this->createJobRunExtractor($this->createJobRun($context)));
        $handler->setTranslator($translator);

        return $handler;
    }

    private function createJobRunExtractor(JobRun $jobRun): JobRunExtractorInterface
    {
        $jobStep = $this->createMock(JobStepInterface::class);
        $jobStep->method('getName')->willReturn('step');
        $jobStep->method('getConfig')->willReturn([
            StepConfig::ELEMENT_CLASS_ID->value => 'CAR',
            StepConfig::ELEMENT_TYPE->value => 'object',
            StepConfig::CONFIG_CONFIGURATION->value => [],
            StepConfig::CONFIG_COLUMNS->value => [],
        ]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);
        $extractor->method('getJobStep')->willReturn($jobStep);

        return $extractor;
    }

    private function createJobRun(array $context): JobRun
    {
        $jobRun = new JobRun(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(1);
        $jobRun->setContext($context);
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        return $jobRun;
    }
}
