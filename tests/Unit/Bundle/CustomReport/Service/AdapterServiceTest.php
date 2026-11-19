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
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterFactoryInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterService;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
final class AdapterServiceTest extends Unit
{
    private array $capturedGetDataArguments = [];

    public function testGetDataPaginatesChartData(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(),
            new ChartDataParameter('testReport', page: 3, pageSize: 20)
        );

        $this->assertSame(40, $this->capturedGetDataArguments[3]);
        $this->assertSame(20, $this->capturedGetDataArguments[4]);
    }

    /**
     * The Studio UI sends no page/pageSize for the CSV export, so the export must contain all rows.
     */
    public function testGetDataDoesNotPaginateExport(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(),
            ExportParameter::fromArray([
                'name' => 'testReport',
                'fields' => ['name'],
                'filters' => ['columnFilters' => [], 'drillDownFilters' => []],
                'page' => 1,
                'pageSize' => 50,
            ])
        );

        $this->assertNull($this->capturedGetDataArguments[3]);
        $this->assertNull($this->capturedGetDataArguments[4]);
    }

    public function testGetDataPaginatesExportWithExplicitPage(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(),
            new ExportParameter('testReport', page: 2, pageSize: 10)
        );

        $this->assertSame(10, $this->capturedGetDataArguments[3]);
        $this->assertSame(10, $this->capturedGetDataArguments[4]);
    }

    /**
     * The export parameter is stored with the job (normalized via its getters) and restored
     * with fromArray() in the collection handler, so the pagination intent must survive that.
     */
    public function testExportPaginationSurvivesJobSerialization(): void
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $roundTrip = static fn (ExportParameter $parameter): ExportParameter => ExportParameter::fromArray(
            json_decode($serializer->serialize($parameter, 'json'), true, 512, JSON_THROW_ON_ERROR)
        );

        $unpaginated = $roundTrip(new ExportParameter('testReport', fields: ['name']));
        $paginated = $roundTrip(new ExportParameter('testReport', fields: ['name'], page: 2, pageSize: 10));

        $this->assertFalse($unpaginated->isPaginated());
        $this->assertTrue($paginated->isPaginated());
        $this->assertSame(2, $paginated->getPage());
        $this->assertSame(10, $paginated->getPageSize());
    }

    private function createReportConfig(): Config
    {
        $config = new Config();
        $config->setDataSourceConfig([['type' => 'sql']]);

        return $config;
    }

    private function createService(): AdapterService
    {
        if (!class_exists(Config::class)) {
            $this->markTestSkipped('The custom reports bundle is not installed.');
        }

        $this->capturedGetDataArguments = [];

        $adapter = $this->makeEmpty(CustomReportAdapterInterface::class, [
            'getData' => function (...$arguments): array {
                $this->capturedGetDataArguments = $arguments;

                return ['data' => [], 'total' => 0];
            },
        ]);

        $factory = $this->makeEmpty(CustomReportAdapterFactoryInterface::class, [
            'create' => $adapter,
        ]);

        return new AdapterService(new ServiceLocator(['sql' => static fn (): mixed => $factory]));
    }
}
