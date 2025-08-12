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

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\DrillDownParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportAdd;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportClone;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface CustomReportServiceInterface
{
    public function getCustomReportTree(): array;

    public function getCustomReportConfigTree(): array;

    /**
     * @throws InvalidArgumentException|NotWriteableException
     */
    public function createCustomReport(CustomReportAdd $parameters): CustomReportDetails;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function updateCustomReport(string $name, CustomReportUpdate $parameters): CustomReportDetails;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function cloneCustomReport(string $reportName, CustomReportClone $parameters): CustomReportDetails;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteCustomReport(string $name): void;

    /**
     * @throws NotFoundException
     */
    public function getCustomReportByName(string $reportName): Config;

    /**
     * @throws NotFoundException
     */
    public function getChartData(ChartDataParameter $parameters): Collection;

    /**
     * @throws NotFoundException
     */
    public function getCustomReportDetails(string $reportName): CustomReportDetails;

    public function getFieldsForExport(Config $reportConfig): array;

    public function generateCsvData(array $reportData, array $exportFields, bool $includeHeaders): array;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function getDrillDownOptions(DrillDownParameter $parameters): array;
}
