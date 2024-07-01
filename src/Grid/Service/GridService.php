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

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final readonly class GridService implements GridServiceInterface
{
    /**
     * @param array<int, ColumnDefinitionInterface> $columnDefinitions
     */
    private array $columnDefinitions;

    /**
     * @param array<int, ColumnResolverInterface> $columnResolvers
     */
    private array $columnResolvers;

    public function __construct(
        ColumnDefinitionLoaderInterface $columnAdapterLoader,
        ColumnResolverLoaderInterface $columnResolverLoader,
        private SystemColumnServiceInterface $systemColumnService
    ) {
        $this->columnDefinitions = $columnAdapterLoader->loadColumnDefinitions();
        $this->columnResolvers = $columnResolverLoader->loadColumnResolvers();
    }

    public function getGridDataForElement(Configuration $configuration, ElementInterface $element): array
    {
         $data = [];
         foreach($configuration->getColumns() as $column) {
             if(!array_key_exists($column->getType(), $this->columnResolvers)) {
                 continue;
             }
             $data[$column->getKey()] = $this->columnResolvers[$column->getType()]->resolve($column, $element);
         }
         return $data;
    }

    public function getAssetGridConfiguration(): Configuration
    {
        $systemColumns = $this->systemColumnService->getSystemColumnsForAssets();
        $columns = [];
        foreach ($systemColumns as $columnKey => $type) {
            if (!array_key_exists($type, $this->columnDefinitions)) {
                continue;
            }
            $columns[] = new Column(
                key: $columnKey,
                group: 'system',
                sortable: $this->columnDefinitions[$type]->isSortable(),
                editable: false,
                localizable: false,
                type: $type,
                config: $this->columnDefinitions[$type]->getConfig()
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

    public function getConfigurationFromArray(array $config): Configuration
    {
        $columns = [];

        foreach ($config['columns'] as $column) {
            $columns[] = new Column(
                key: $column['key'],
                group: $column['group'],
                sortable: $column['sortable'],
                editable: $column['editable'],
                localizable: $column['localizable'],
                type: $column['type'],
                config: $column['config']
            );
        }
        return new Configuration($columns);
    }
}
