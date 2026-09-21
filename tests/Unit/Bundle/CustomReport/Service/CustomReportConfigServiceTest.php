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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\CustomReport\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigService;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\TransferDataValidator;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final class CustomReportConfigServiceTest extends Unit
{
    public function testImportRejectsInvalidJson(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not valid JSON');

        $this->createService($repository)->importCustomReport('{not json');
    }

    public function testImportRejectsPayloadWithoutName(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing report "name"');

        $this->createService($repository)->importCustomReport('{"sql": "SELECT 1"}');
    }

    public function testImportRejectsMalformedPropertyTypesWithoutSaving(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for "niceName": expected string, got array.');

        $this->createService($repository)->importCustomReport('{"name": "BadReport", "niceName": []}');
    }

    public function testImportRejectsMalformedColumnConfigurationWithoutSaving(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for "columnConfiguration[0].name": expected string, got array.');

        $this->createService($repository)->importCustomReport(
            '{"name": "BadReport", "columnConfiguration": [{"name": [], "display": true, "export": true, "order": true}]}'
        );
    }

    public function testImportRejectsColumnsWithoutRequiredFlagsWithoutSaving(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for "columnConfiguration[0]": missing required field "display".');

        $this->createService($repository)->importCustomReport(
            '{"name": "BadReport", "columnConfiguration": [{"name": "id"}]}'
        );
    }

    public function testExportDeniesReportsTheCurrentUserMayNotAccess(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->method('loadByNameForCurrentUser')->with('HiddenReport')->willReturn(null);
        $repository->expects($this->never())->method('extractTransferableData');

        $downloadService = $this->createMock(DownloadServiceInterface::class);
        $downloadService->expects($this->never())->method('downloadJSON');

        $this->expectException(ForbiddenException::class);

        $this->createService($repository, downloadService: $downloadService)->exportCustomReport('HiddenReport');
    }

    public function testExportDownloadsTransferableDataAsPrettyPrintedJson(): void
    {
        $report = new Config();
        $report->setName('MyReport');

        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->method('loadByNameForCurrentUser')->with('MyReport')->willReturn($report);
        $repository->method('extractTransferableData')->with($report)->willReturn([
            'name' => 'MyReport',
            'niceName' => 'My Report',
            'sharedUserNames' => ['editor'],
        ]);

        $response = new Response('{}');
        $downloadService = $this->createMock(DownloadServiceInterface::class);
        $downloadService->expects($this->once())
            ->method('downloadJSON')
            ->with(
                "{\n    \"name\": \"MyReport\",\n    \"niceName\": \"My Report\",\n    \"sharedUserNames\": [\n        \"editor\"\n    ]\n}",
                'custom_report_MyReport_export.json'
            )
            ->willReturn($response);

        $result = $this->createService($repository, downloadService: $downloadService)->exportCustomReport('MyReport');

        $this->assertSame($response, $result);
    }

    public function testImportRejectsExistingReportName(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->method('exists')->with('ExistingReport')->willReturn(true);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->createService($repository)->importCustomReport('{"name": "ExistingReport"}');
    }

    public function testImportCreatesReportFromUploadedData(): void
    {
        $importedConfig = new Config();
        $importedConfig->setName('ImportedReport');

        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->method('exists')->with('ImportedReport')->willReturn(false);
        $repository->expects($this->once())
            ->method('importConfig')
            ->with('ImportedReport', ['name' => 'ImportedReport', 'niceName' => 'Imported'])
            ->willReturn($importedConfig);

        $details = new CustomReportDetails(
            'ImportedReport',
            '',
            [],
            'Imported',
            '',
            '',
            '',
            true,
            '',
            '',
            0,
            0,
            [],
            [],
            true,
            true
        );
        $hydrator = $this->createMock(CustomReportHydratorInterface::class);
        $hydrator->method('extractReportDetails')->with($importedConfig)->willReturn($details);

        $result = $this->createService($repository, $hydrator)->importCustomReport(
            '{"name": "ImportedReport", "niceName": "Imported"}'
        );

        $this->assertSame($details, $result);
    }

    public function testImportRejectsUnknownAdapterTypeWithoutSaving(): void
    {
        $repository = $this->createMock(CustomReportRepositoryInterface::class);
        $repository->expects($this->never())->method('importConfig');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for "dataSourceConfig[0].type": unknown adapter type "graphql".');

        $this->createService($repository)->importCustomReport(
            '{"name": "BadReport", "dataSourceConfig": [{"type": "graphql"}]}'
        );
    }

    private function createAdapterService(): AdapterServiceInterface
    {
        $adapterService = $this->createMock(AdapterServiceInterface::class);
        $adapterService->method('hasAdapter')->willReturnCallback(
            static fn (string $type): bool => $type === 'sql'
        );

        return $adapterService;
    }

    private function createService(
        CustomReportRepositoryInterface $repository,
        ?CustomReportHydratorInterface $hydrator = null,
        ?DownloadServiceInterface $downloadService = null
    ): CustomReportConfigService {
        return new CustomReportConfigService(
            $hydrator ?? $this->createMock(CustomReportHydratorInterface::class),
            $repository,
            $this->createMock(EventDispatcherInterface::class),
            $downloadService ?? $this->createMock(DownloadServiceInterface::class),
            new TransferDataValidator($this->createAdapterService()),
        );
    }
}
