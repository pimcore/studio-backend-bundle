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

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface CustomReportServiceInterface
{
    public function getCustomReportTree(): array;

    public function getCustomReportConfigTree(): array;

    /**
     * @throws NotFoundException
     */
    public function getCustomReportByName(string $reportName): Config;

    /**
     * @throws NotFoundException
     */
    public function getChartData(string $reportName, ExportParameter $chartDataParameter): CustomReportChartData;

    /**
     * @throws NotFoundException
     */
    public function getCustomReportDetails(string $reportName): CustomReportDetails;

    public function getFieldsForExport(Config $reportConfig): array;

    public function generateCsvData(array $reportData, array $exportFields, bool $includeHeaders): array;
}
