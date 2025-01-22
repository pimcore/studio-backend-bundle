<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Service;

use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterFactoryInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ExportParameter;
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

    public function getData(Config $report, ExportParameter $chartDataParameter): array
    {
        return $this->getAdapter($report)->getData(
            $chartDataParameter->getFilters(),
            $chartDataParameter->getSortBy(),
            $chartDataParameter->getSortOrder(),
            $chartDataParameter->getReportOffset(),
            $chartDataParameter->getReportLimit(),
            $chartDataParameter->getFields(),
            $chartDataParameter->getDrillDownFilters()
        );
    }

    /**
     * @throws NotFoundException
     */
    private function getAdapter(Config $report): CustomReportAdapterInterface
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
