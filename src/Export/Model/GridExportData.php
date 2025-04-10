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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Model;

/**
 * @internal
 */
final readonly class GridExportData
{
    public function __construct(
        private array $columns,
        private array $exportData,
        private array $exportDataInfo,
        private bool $withHeaders = false,
        private bool $withGroup = false,
    ) {
    }
    
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getExportData(): array
    {
        return $this->exportData;
    }

    public function getExportDataInfo(): array
    {
        return $this->exportDataInfo;
    }

    public function isWithHeaders(): bool
    {
        return $this->withHeaders;
    }

    public function isWithGroup(): bool
    {
        return $this->withGroup;
    }
}
