# Extending Grid with Custom Columns

The grid in Studio is based on Column Definitions that define how the columns are represented, column resolvers that define how the data is obtained from the object and column collectors that show what type of columns are available.
E.g. the metadata for the asset grid have its own column definitions, resolvers and collectors.

## How to add a custom column

In order that the grid can work with a custom column you have to implement the following classes:
- Column Definition with the `ColumnDefinitionInterface` and tag it with `pimcore.studio_backend.column_definition`
- Column Resolver with the `ColumnResolverInterface` and tag it with `pimcore.studio_backend.column_resolver`

  You must implement the `CoreElementColumnResolverInterface` or `StudioElementColumnResolverInterface` depending on it the data is coming from the or the studio (GDI).
  It is also possible to implement both interfaces. But `CoreElementColumnResolverInterface` has a higher priority than `StudioElementColumnResolverInterface`.
- Column Collector with the `ColumnCollectorInterface` and tag it with `pimcore.studio_backend.column_collector`

Once everything is implemented column will show up in the available columns in the grid configuration e.g. via `/studio/api/assets/grid/available-configuration`
On more infos on how to use the columns see the [Grid](../03_Grid.md)

### Example Column Definition

```php
<?php
declare(strict_types=1);

namespace App\Grid\Column\Definition\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;


final readonly class CheckboxDefinition implements ColumnDefinitionInterface
{
    public function getType(): string
    {
        return 'checkbox';
    }

    public function getConfig(mixed $config): array
    {
        return  [];
    }

    public function isSortable(): bool
    {
        return false;
    }

    public function getFrontendType(): string
    {
        return 'checkbox';
    }

    public function isExportable(): bool
    {
        return true;
    }
}

```

### Example Column Resolver

```php
<?php
declare(strict_types=1);

namespace App\Grid\Column\Resolver\Metadata;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;


final class FullPathResolver implements ColumnResolverInterface, StudioElementColumnResolverInterface, CoreElementColumnResolverInterface
{
    public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
    {
        return new ColumnData($column->getKey(), $column->getLocale(), $element->getFullPath());
    }
    
    
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        return new ColumnData($column->getKey(), $column->getLocale(), $element->getFullPath());
    }

    public function getType(): string
    {
        return 'fullpath';
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

### Example Column Collector

```php
<?php
declare(strict_types=1);

namespace App\Grid\Column\Collector;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function array_key_exists;


final readonly class MyCollector implements ColumnCollectorInterface
{
    public function __construct(
        private SomeRepositoryInterface $someRepository,
    ) {
    }

    public function getCollectorName(): string
    {
        return 'my-collector';
    }

    /**
     * @param ColumnDefinitionInterface[] $availableColumnDefinitions
     *
     * @return ColumnConfiguration[]
     */
    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
         // availableColumnDefinitions are provided by the grid service 
         return [
            new ColumnConfiguration(
                key: 'checkbox_key',
                group: 'my_group',
                sortable: $availableColumnDefinitions['checkbox']->isSortable(),
                editable: false,
                localizable: false,
                locale: null,
                type: 'checkbox',
                frontendType: $availableColumnDefinitions['checkbox']->getFrontendType(),
                config: $availableColumnDefinitions['checkbox']->getConfig()
            )
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

### Predefined Columns for Grids
You can define what columns should be visible by default in the grid by modifying the `pimcore_studio_backend.grid.asset.predefined_columns` parameter in your `config.yaml` file.

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

### Predefined Columns for Search Grids

You can define what columns should be visible by default in the search grid by modifying the `pimcore_studio_backend.search_grid.asset.predefined_columns` parameter in your `config.yaml` file.

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

### Transformers for Advanced Columns
You can also define transformers for Advanced Columns. These transformers will be used to transform the data before it is displayed in the grid.
You need to implement the `TransformerInterface` and tag it with `pimcore.studio_backend.grid_transformer`.

`transform` must return an array of `AdvancedValue` objects, which are used to represent the transformed values in the grid.
If there is an error during the transformation, you can throw a `Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException` with an error message.

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
        foreach ($value as $key => $val) {
            $value->setValue(strtoupper($val->getValue()));
        }
        
        return $value
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