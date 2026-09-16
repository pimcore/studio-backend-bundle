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
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterService;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final class AdapterServiceTest extends Unit
{
    private array $capturedGetDataArguments = [];

    public function testGetDataFallsBackToSortConfiguredInReport(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(['orderby' => 'creationDate', 'orderbydir' => 'DESC']),
            new ChartDataParameter('testReport')
        );

        $this->assertSame('creationDate', $this->capturedGetDataArguments[1]);
        $this->assertSame('DESC', $this->capturedGetDataArguments[2]);
    }

    public function testGetDataDefaultsConfiguredSortDirectionToAscending(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(['orderby' => 'creationDate', 'orderbydir' => null]),
            new ChartDataParameter('testReport')
        );

        $this->assertSame('creationDate', $this->capturedGetDataArguments[1]);
        $this->assertSame('ASC', $this->capturedGetDataArguments[2]);
    }

    public function testGetDataPrefersExplicitSortOverConfiguredSort(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(['orderby' => 'creationDate', 'orderbydir' => 'DESC']),
            new ChartDataParameter('testReport', sortBy: 'name', sortOrder: 'ASC')
        );

        $this->assertSame('name', $this->capturedGetDataArguments[1]);
        $this->assertSame('ASC', $this->capturedGetDataArguments[2]);
    }

    public function testGetDataWithoutExplicitAndConfiguredSort(): void
    {
        $service = $this->createService();

        $service->getData(
            $this->createReportConfig(['orderby' => '']),
            new ChartDataParameter('testReport')
        );

        $this->assertNull($this->capturedGetDataArguments[1]);
        $this->assertNull($this->capturedGetDataArguments[2]);
    }

    private function createReportConfig(array $dataSourceConfig): Config
    {
        $config = new Config();
        $config->setDataSourceConfig([array_merge(['type' => 'sql'], $dataSourceConfig)]);

        return $config;
    }

    private function createService(): AdapterService
    {
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
