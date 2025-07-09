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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service;

use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterFactoryInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use stdClass;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final readonly class AdapterService implements AdapterServiceInterface
{
    public function __construct(
        private ServiceLocator $adapters
    ) {
    }

    public function getData(Config $report, ChartDataParameter $chartDataParameter): array
    {
        return $this->getAdapter($report)->getData(
            $chartDataParameter->getFilters()->getColumnFilters(),
            $chartDataParameter->getSortBy(),
            $chartDataParameter->getSortOrder(),
            ($chartDataParameter->getPage() - 1) * $chartDataParameter->getPageSize(),
            $chartDataParameter->getPageSize(),
            $chartDataParameter->getFields(),
            $chartDataParameter->getFilters()->getDrillDownFilters()
        );
    }

    /**
     * @throws NotFoundException
     */
    public function getAdapter(Config $report): CustomReportAdapterInterface
    {
        $configuration = $report->getDataSourceConfig();
        if (!$configuration instanceof stdClass) {
            throw new NotFoundException('Datasource', $report->getName(), 'name');
        }

        $type = $configuration->type ?? 'sql';
        if (!$this->adapters->has($type)) {
            throw new NotFoundException('Adapter', $type, 'type');
        }

        /** @var CustomReportAdapterFactoryInterface $factory */
        $factory = $this->adapters->get($type);

        return $factory->create($configuration, $report);
    }
}
