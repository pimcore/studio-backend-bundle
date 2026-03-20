---
title: Filters
description: Overview of search index filters and listing filters.
---

# Extending Filters

The Studio Backend has two filter systems: **Search Index Filters** (for OpenSearch/Elasticsearch
via Generic Data Index) and **Listing Filters** (for classic Pimcore listings). Each filter
knows which type it handles and applies only in the matching context.

## How Filters Work

Each filter system has a filter service that loads all tagged filter services and calls `apply()`
on each one. The filter itself decides whether to act based on the parameters it receives.
For listing filters, only filters that support the specific listing type are loaded.

### Query Filters with Mapped Parameters
In this example, the query parameters are mapped via `#[MapQueryString]` into the request object, which is then used in the filter itself.

```php
<?php
declare(strict_types=1);

namespace App\MappedParameter;

interface CollectionParametersInterface
{
    public function getPage(): int;

    public function getPageSize(): int;
}


```

```php
<?php
declare(strict_types=1);

namespace App\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;

final class PageFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof CollectionParametersInterface) {
            return $query;
        }

        return $query->setPage($parameters->getPage());
    }
}

``` 

### Column Filters with Payload
This approach should unify how the filtering is done within the system and to have a consistent payload over all endpoints.

The key difference here is that the payload is mapped via `#[MapRequestPayload]` into the request object. The request object contains the FilterParameter class, that holds all the filter data.
The `FilterParameter` has methods to return all the filters by type. It can also only return the first filter by type.
In the apply method, you can check if the filter is applicable to request the specific type of the FilterParameter.

The basic filter payload for the columns looks like the following:

```json
...
"columnFilters" [
  {
    "key": "selectKey",
    "type": "metadata.select",
    "filterValue": "selectValue"
  }
]
...
```

The `key` is the key of the column you want to filter by.  
The `type` is the type of the filter you want to apply.  
The `filterValue` is the value you want to filter by.  

Query parameters can also be mapped to a payload using a custom mapper.

```php
<?php
declare(strict_types=1);


namespace App\Listing\Filter;

use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;

final readonly class LikeFilter implements FilterInterface
{
    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        $equalsColumn = $parameters->getFirstColumnFilterByType(FilterType::LIKE->value);

        if ($equalsColumn === null) {
            return $listing;
        }

        $listing->addConditionParam(
            $equalsColumn->getKey() . ' LIKE :' . $equalsColumn->getKey(),
            [$equalsColumn->getKey() => "%{$equalsColumn->getFilterValue()}%"]
        );

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }
}
```


## Search Index Filters
For assets, data objects and documents we are using OpenSearch or ElasticSearch (based on your Generic Data Index 
configuration) to index the data and to provide a fast search.
For more details on how to implement these filters see the [Search Index Filters](./01_Extending_Search_Index_Filters.md).

## Listing Filters
For the classic approach of using listings, the Listing Filters are used.
For more details on how to implement the Listing filters see the [Listing Filters](./02_Extending_Listing_Filters.md).