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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapperInterface;
use Pimcore\Model\Asset\Service;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class SystemColumnService implements SystemColumnServiceInterface
{
    public function __construct(
        private ColumnMapperInterface $columnMapper
    ) {
    }

    public function getSystemColumnsForAssets(): array
    {
        $systemColumns = Service::GRID_SYSTEM_COLUMNS;
        $columns = [];
        foreach ($systemColumns as $column) {
            $columns[$column] = $this->columnMapper->getType($column);
        }

        return $columns;
    }

    public function getSystemColumnsForDataObjects(): array
    {
        $systemColumns = Concrete::SYSTEM_COLUMN_NAMES;
        $columns = [];
        foreach ($systemColumns as $column) {
            $columns[$column] = $this->columnMapper->getType($column);
        }

        return $columns;
    }
}
