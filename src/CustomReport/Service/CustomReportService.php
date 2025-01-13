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
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Extractor\DataExtractorInterface;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Model\User;
use RuntimeException;

/**
 * @internal
 */
final readonly class CustomReportService implements CustomReportServiceInterface
{
    public function __construct(
        private DataExtractorInterface $dataExtractor,
        private CustomReportRepositoryInterface $customReportRepository,
        private AdapterServiceInterface $adapterService,
    ) {
    }

    public function getCustomReportTree(?User $user = null): array
    {
        return $this->dataExtractor->extractTree(
            $user ?
                $this->customReportRepository->loadForUser($user) :
                $this->customReportRepository->loadForCurrentUser()
        );
    }

    public function getCustomReportConfigTree(?User $user = null): array
    {
        return $this->dataExtractor->extractConfigTree(
            $user ?
                $this->customReportRepository->loadForUser($user) :
                $this->customReportRepository->loadForCurrentUser()
        );
    }

    public function getCustomReportByName(string $reportName): Config
    {
        $report = $this->customReportRepository->loadByName($reportName);
        if(!$report) {
            throw new RuntimeException('Report ' . $reportName . ' not found');
        }
        return $report;
    }

    public function getChartData(string $reportName, ChartDataParameter $chartDataParameter): array
    {
        $reportConfig = $this->getCustomReportByName($reportName);

        $items = $this->adapterService->getData($reportConfig, $chartDataParameter);
        return $this->dataExtractor->extractChartData(
            $items
        );
    }

    public function getCustomReportDetails(string $reportName): array {
        $config = $this->getCustomReportByName($reportName);

        return $this->dataExtractor->extractReportDetails($config);
    }
}
