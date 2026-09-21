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
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    private function createService(
        CustomReportRepositoryInterface $repository,
        ?CustomReportHydratorInterface $hydrator = null
    ): CustomReportConfigService {
        return new CustomReportConfigService(
            $hydrator ?? $this->createMock(CustomReportHydratorInterface::class),
            $repository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(DownloadServiceInterface::class),
        );
    }
}
