---
title: Custom Grid Columns
description: Add custom column types, resolvers, and collectors to the element grid.
---

# Extending Grid with Custom Columns

## Overview

The Studio grid displays element data in configurable columns. Each
column is powered by three backend components:

- **Column Definition** - declares the column type, its capabilities
  (sortable, filterable, exportable), and which frontend cell type
  renders it.
- **Column Resolver** - fetches the actual value for a given element
  and column.
- **Column Collector** - lists the available columns that users can
  add to their grid configuration.

For data objects, columns are generated automatically from class
definitions. For custom column types or non-standard sources, you
register your own definition, resolver, and collector.

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete progress bar custom grid column example.

---

## Column Definition

Implement `ColumnDefinitionInterface` and tag the service with
`pimcore.studio_backend.grid_column_definition`.

```php
interface ColumnDefinitionInterface
{
    public function getType(): string;
    public function getConfig(mixed $config): array;
    public function isSortable(): bool;
    public function isFilterable(): bool;
    public function getFrontendType(): string;
    public function isExportable(): bool;
}
```

### Method Reference

| Method | Purpose |
|---|---|
| `getType()` | Unique type identifier (e.g. `'checkbox'`, `'data-object.simpleText'`) |
| `getConfig()` | Extra config passed to the frontend cell |
| `isSortable()` | Whether the column supports server-side sorting |
| `isFilterable()` | Whether the column supports grid filtering |
| `isExportable()` | Whether the column is included in exports |
| `getFrontendType()` | Determines which grid cell component renders the column |

### The Role of `getFrontendType()`

The `getFrontendType()` return value tells the frontend which cell
renderer to use. If the value matches a
[built-in frontend type](#built-in-frontend-types), no frontend work
is needed. If you return a custom string, you must register a
matching `DynamicTypeGridCellAbstract` subclass in the Studio UI
whose `id` property equals your `getFrontendType()` value.

### Example

```php
<?php
declare(strict_types=1);

namespace App\Grid\Column\Definition;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;

final readonly class ProgressBarDefinition implements
    ColumnDefinitionInterface
{
    public function getType(): string
    {
        return 'progressBar';
    }

    public function getConfig(mixed $config): array
    {
        return [];
    }

    public function isSortable(): bool
    {
        return true;
    }

    public function isFilterable(): bool
    {
        return false;
    }

    public function getFrontendType(): string
    {
        // Custom type - requires a frontend cell registration.
        // Use a built-in FrontendType value to skip frontend work.
        return 'progress-bar';
    }

    public function isExportable(): bool
    {
        return true;
    }
}
```

---

## Column Resolver

Implement `ColumnResolverInterface` together with one or both of the
element-specific interfaces:

| Interface | Resolves From | Priority |
|---|---|---|
| `CoreElementColumnResolverInterface` | `ElementInterface` (Pimcore core) | Higher |
| `StudioElementColumnResolverInterface` | `StudioElementInterface` (GDI) | Lower |

If both interfaces are implemented, `CoreElementColumnResolverInterface`
takes precedence.

Tag the service with `pimcore.studio_backend.grid_column_resolver`.

### ColumnData Constructor

The `ColumnData` value object requires four arguments (plus an
optional inheritance parameter):

```php
new ColumnData(
    key: string,
    locale: ?string,
    value: mixed,
    fieldType: mixed,
    inheritance: null|InheritanceData|array  // optional
)
```

### Example

```php
<?php
declare(strict_types=1);

namespace App\Grid\Column\Resolver;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;

final class ProgressBarResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    StudioElementColumnResolverInterface
{
    public function resolveForCoreElement(
        Column $column,
        ElementInterface $element
    ): ColumnData {
        return new ColumnData(
            key: $column->getKey(),
            locale: $column->getLocale(),
            value: $element->getProperty('progress') ?? 0,
            fieldType: 'progressBar'
        );
    }

    public function resolveForStudioElement(
        Column $column,
        StudioElementInterface $element
    ): ColumnData {
        return new ColumnData(
            key: $column->getKey(),
            locale: $column->getLocale(),
            value: 0,
            fieldType: 'progressBar'
        );
    }

    public function getType(): string
    {
        return 'progressBar';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
```

---

## Column Collector

Implement `ColumnCollectorInterface` and tag the service with
`pimcore.studio_backend.grid_column_collector`.

The collector receives all registered column definitions keyed by
type. It returns `ColumnConfiguration` objects describing the
columns available to users.

### ColumnConfiguration Constructor

All 11 parameters are required:

```php
new ColumnConfiguration(
    key: string,
    group: array,
    sortable: bool,
    editable: bool,
    exportable: bool,
    filterable: bool,
    localizable: bool,
    locale: ?string,
    type: string,
    frontendType: string,
    config: array,
)
```

### Example

```php
<?php
declare(strict_types=1);

namespace App\Grid\Column\Collector;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

final readonly class ProgressBarCollector implements
    ColumnCollectorInterface
{
    public function getCollectorName(): string
    {
        return 'progress-bar';
    }

    /**
     * @param ColumnDefinitionInterface[] $availableColumnDefinitions
     *
     * @return ColumnConfiguration[]
     */
    public function getColumnConfigurations(
        array $availableColumnDefinitions
    ): array {
        $definition = $availableColumnDefinitions['progressBar'];

        return [
            new ColumnConfiguration(
                key: 'progress',
                group: ['progress'],
                sortable: $definition->isSortable(),
                editable: false,
                exportable: $definition->isExportable(),
                filterable: $definition->isFilterable(),
                localizable: false,
                locale: null,
                type: 'progressBar',
                frontendType: $definition->getFrontendType(),
                config: $definition->getConfig(null)
            ),
        ];
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
```

---

## Service Registration

Register all three components with their respective DI tags:

| Component | Tag |
|---|---|
| Column Definition | `pimcore.studio_backend.grid_column_definition` |
| Column Resolver | `pimcore.studio_backend.grid_column_resolver` |
| Column Collector | `pimcore.studio_backend.grid_column_collector` |

```yaml
services:
    App\Grid\Column\Definition\ProgressBarDefinition:
        tags:
            - { name: pimcore.studio_backend.grid_column_definition }

    App\Grid\Column\Resolver\ProgressBarResolver:
        tags:
            - { name: pimcore.studio_backend.grid_column_resolver }

    App\Grid\Column\Collector\ProgressBarCollector:
        tags:
            - { name: pimcore.studio_backend.grid_column_collector }
```

Once registered, the column appears in the available columns API
(e.g. `/pimcore-studio/api/assets/grid/available-configuration`).

To skip a custom data object field type from automatic column
generation, add it to the skip list:

```yaml
pimcore_studio_backend:
    grid:
        data_object:
            skip_field_types:
                - "myCustomFieldType"
```

For more information on grid architecture, see the
[Grid](../01_Architecture_Overview/01_Grid.md) documentation.

---

## Built-in Frontend Types

The `FrontendType` enum provides the following built-in values. When
`getFrontendType()` returns one of these, the Studio UI renders the
column without any frontend plugin work:

| Enum Case | String Value |
|---|---|
| `ELEMENT_DROPZONE` | `element_dropzone` |
| `INPUT` | `input` |
| `ID` | `id` |
| `TEXTAREA` | `textarea` |
| `SELECT` | `select` |
| `MULTISELECT` | `multiselect` |
| `CHECKBOX` | `checkbox` |
| `DATETIME` | `datetime` |
| `IMAGE` | `image` |
| `ASSET_LINK` | `asset-link` |
| `OBJECT_LINK` | `object-link` |
| `ASSET_PREVIEW` | `asset-preview` |
| `BOOLEAN` | `boolean` |

If you return a custom string not in this list, you must register a
matching frontend cell type. See the
[Custom Grid Columns](https://github.com/pimcore/pimcore/blob/2026.x/doc/10_Extending_Pimcore/03_Custom_Extension_Guides/11_Custom_Grid_Columns.md)
cross-layer guide for details.

---

## Predefined Columns for Grids

You can define what columns should be visible by default in the grid
by modifying the `pimcore_studio_backend.grid.asset.predefined_columns`
parameter in your `config.yaml` file.

Example:

```yaml
pimcore_studio_backend:
    grid:
        asset:
            predefined_columns:
                - key: id
                  group: system
                - key: fullpath
                  group: system
        data_object:
            predefined_columns:
                - key: id
                  group: system
                - key: fullpath
                  group: system
```

## Predefined Columns for Search Grids

You can define what columns should be visible by default in the
search grid by modifying the
`pimcore_studio_backend.search_grid.asset.predefined_columns`
parameter in your `config.yaml` file.

Example:

```yaml
pimcore_studio_backend:
    search_grid:
        asset:
            predefined_columns:
                - key: id
                  group: system
                - key: fullpath
                  group: system
```

---

## Transformers

You can define transformers for Advanced Columns. Transformers
modify column data before it is displayed in the grid. Implement
`TransformerInterface` and tag with
`pimcore.studio_backend.grid_transformer`.

The `transform` method receives and returns an array of
`AdvancedValue` objects. If transformation fails, throw a
`Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException`.

```php
<?php
declare(strict_types=1);

namespace App\Transformer;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;

final class Uppercase implements TransformerInterface
{
    /**
     * @param AdvancedValue[] $value
     * @return AdvancedValue[]
     */
    public function transform(array $value, array $config): array
    {
        foreach ($value as $val) {
            $val->setValue(strtoupper($val->getValue()));
        }

        return $value;
    }

    public function getName(): string
    {
        return 'Uppercase';
    }

    public function getKey(): string
    {
        return 'uppercase';
    }

    public function getDescription(): string
    {
        return 'Transforms the value to uppercase.';
    }

    public function getConfigOptions(): array
    {
        return [];
    }
}
```

---

## Cross-Layer Guide

For custom frontend types that require frontend registration, see
the
[Custom Grid Columns](https://github.com/pimcore/pimcore/blob/2026.x/doc/10_Extending_Pimcore/03_Custom_Extension_Guides/11_Custom_Grid_Columns.md)
cross-layer guide.

## Example Bundle

See the
[studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
for a complete working example including backend definition,
resolver, collector, and frontend cell type.
