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
            $column = $this->mapCustomColumnKeys($column);

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

        // Add missing columns
        $columns['type'] = 'string';
        $columns['userModification'] = 'user';
        $columns['userOwner'] = 'user';

        return $columns;
    }

    private function mapCustomColumnKeys(string $key): string
    {
        $keyMapping = [
            'size' => 'fileSize',
        ];

        return $keyMapping[$key] ?? $key;
    }
}
