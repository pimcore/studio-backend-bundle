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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Extractor;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config;

/**
 * @internal
 */
final readonly class DataExtractor implements DataExtractorInterface
{
    public function extractTree(array $reports): array
    {
        $data = [];

        foreach ($reports as $report) {
            if ($report->getDataSourceConfig() !== null) {
                $data[] = [
                    'name' => htmlspecialchars($report->getName()),
                    'niceName' => htmlspecialchars($report->getNiceName()),
                    'iconClass' => htmlspecialchars($report->getIconClass()),
                    'group' => htmlspecialchars($report->getGroup()),
                    'groupIconClass' => htmlspecialchars($report->getGroupIconClass()),
                    'menuShortcut' => $report->getMenuShortcut(),
                    'reportClass' => htmlspecialchars($report->getReportClass()),
                ];
            }
        }

        return $data;
    }

    public function extractConfigTree(array $reports): array
    {
        $data = [];

        foreach ($reports as $item) {
            $data[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
                'cls' => 'pimcore_treenode_disabled',
                'writeable' => $item->isWriteable(),
            ];
        }

        return $data;
    }

    public function extractReportDetails(Config $reportConfig): array
    {
        return [
            ... $reportConfig->getObjectVars(),
            'writeable' => $reportConfig->isWriteable(),
        ];
    }

    public function extractChartData(array $chartData): array
    {
        return $chartData['data'] ?? [];
    }
}
