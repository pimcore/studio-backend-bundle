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

use Pimcore\Bundle\StudioBackendBundle\Grid\Adapter\ColumnAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnDefinition;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final readonly class GridService implements GridServiceInterface
{
    /**
     * @param array<int, ColumnAdapterInterface> $adapters
     */
    private array $adapters;

    public function __construct(
        AdapterLoaderInterface $adapterLoader,
        private SystemColumnServiceInterface $systemColumnService
    ) {
        $this->adapters = $adapterLoader->loadAdapters();
    }
    public function getGridDataForElement(Configuration $configuration, ElementInterface $element): array
    {
      // $data = [];
      // foreach($configuration->getColumns() as $column) {
      //     $data[$column->getKey()] = $column->getData($element);
      // }

      // return $data;
        return [];
    }

    public function getAssetGridConfiguration(): Configuration
    {
        $systemColumns = $this->systemColumnService->getSystemColumnsForAssets();
        $columns = [];
        foreach($systemColumns as $columnKey => $type) {
            if(!array_key_exists($type, $this->adapters)) {
                continue;
            }
            $columns[] = new ColumnDefinition(
                key: $columnKey,
                group: 'system',
                sortable: $this->adapters[$type]->isSortable(),
                editable: false,
                localizable: false,
                type: $type,
                config: $this->adapters[$type]->getConfig()
            );
        }
        return new Configuration($columns);
    }

    public function getDocumentGridColumns(): Configuration
    {
        return new Configuration([]);
    }

    public function getDataObjectGridColumns(ClassDefinition $classDefinition): Configuration
    {
        return new Configuration([]);
    }
}
