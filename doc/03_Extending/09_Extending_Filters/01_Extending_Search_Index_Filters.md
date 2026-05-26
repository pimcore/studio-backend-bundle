---
title: Search Index Filters
description: Add custom filters for OpenSearch/Elasticsearch queries.
---

# Extending Search Index Filters

Search Index Filters build on the [Generic Data Index Bundle](https://github.com/pimcore/generic-data-index-bundle)
and add filter logic to OpenSearch or Elasticsearch queries for assets, data objects, and documents.

## Adding a Search Index Filter

Implement `Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface` and register the
service with the tag `pimcore.studio_backend.search_index.filter`.

## Available Search Modifiers

For the full list of search modifiers, see the
[Generic Data Index documentation](https://github.com/pimcore/generic-data-index-bundle/blob/2026.x/doc/04_Searching_For_Data_In_Index/05_Search_Modifiers/README.md).


#### Example

```php
<?php
declare(strict_types=1);

namespace App\MyBundle\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\OrderByField;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;


final class MyFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        $search = $query->getSearch();
        
        $search->addModifier(new OrderByField('myfield', SortDirection::ASC));
        
        return $query;
    }
}
```