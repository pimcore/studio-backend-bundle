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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SystemColumnServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function array_key_exists;

/**
 * @internal
 */
final readonly class SystemFieldCollector implements ColumnCollectorInterface
{
    public function __construct(
        private SystemColumnServiceInterface $systemColumnService,
        private DataObjectResolverInterface $dataObjectResolver
    ) {
    }

    public function getCollectorName(): string
    {
        return $this->getTypeName() . '.dataobject';
    }

    /**
     * @param ColumnDefinitionInterface[] $availableColumnDefinitions
     *
     * @return ColumnConfiguration[]
     */
    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        $systemColumns = $this->systemColumnService->getSystemColumnsForDataObjects();
        $columns = [];
        foreach ($systemColumns as $columnKey => $type) {
            $type = $this->concatType($type);
            if (!array_key_exists($type, $availableColumnDefinitions)) {
                continue;
            }

            $column = new ColumnConfiguration(
                key: $columnKey,
                group: [$this->getTypeName()],
                sortable: $this->overrideSortable($availableColumnDefinitions[$type], $columnKey),
                editable: $this->isSystemFieldEditable($columnKey),
                exportable: $availableColumnDefinitions[$type]->isExportable(),
                filterable: $this->overrideFilterable($availableColumnDefinitions[$type], $columnKey),
                localizable: false,
                locale: null,
                type: $availableColumnDefinitions[$type]->getType(),
                frontendType: $this->getCustomFrontendAdapter(
                    $columnKey,
                    $availableColumnDefinitions[$type]->getFrontendType()
                ),
                config: $this->getCustomConfig($columnKey)
            );

            $columns[] = $column;
        }

        return $columns;
    }

    private function overrideSortable(ColumnDefinitionInterface $definition, string $column): bool
    {
        return match ($column) {
            'filename',  => false,
            default => $definition->isSortable(),
        };
    }

    private function overrideFilterable(ColumnDefinitionInterface $definition, string $column): bool
    {
        return match ($column) {
            'filename', 'index', 'classname',  => false,
            default => $definition->isSortable(),
        };
    }

    private function isSystemFieldEditable(string $systemField): bool
    {
        return match ($systemField) {
            'published',  => true,
            default => false,
        };
    }

    private function getCustomFrontendAdapter(string $columnKey, string $defaultAdapter): string
    {
        $customFrontendAdapters = [
            'type' => FrontendType::MULTISELECT->value,
            'fullpath' => FrontendType::OBJECT_LINK->value,
        ];

        if (array_key_exists($columnKey, $customFrontendAdapters)) {
            return $customFrontendAdapters[$columnKey];
        }

        return $defaultAdapter;
    }

    private function getCustomConfig(string $columnKey): array
    {
        $customConfig = [
            'type' => $this->getTypeConfig(),
        ];

        if (array_key_exists($columnKey, $customConfig)) {
            return $customConfig[$columnKey];
        }

        return [];
    }

    private function getTypeConfig(): array
    {
        return [
            'fieldDefinition' => [
                'options' => array_map(
                    static fn ($type) => ['key' => ucfirst($type), 'value' => $type ],
                    $this->dataObjectResolver->getTypes()
                ),
            ],
        ];
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_DATA_OBJECT,
        ];
    }

    private function concatType(string $type): string
    {
        return $this->getTypeName() . '.' . $type;
    }

    private function getTypeName(): string
    {
        return 'system';
    }
}
