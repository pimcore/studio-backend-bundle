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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Hydrator;

/**
 * @internal
 */
final readonly class CustomReportHydrator implements CustomReportHydratorInterface
{
    public function hydrate(array $reports): array
    {
        $hydratedReports = [];

        foreach ($reports as $report) {
            if ($report->getDataSourceConfig() !== null) {
                $hydratedReports[] = [
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

        return $hydratedReports;
    }
}
