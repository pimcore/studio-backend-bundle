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
This approach unifies filtering across all endpoints with a consistent payload.

The payload maps via `#[MapRequestPayload]` into the request object, which contains the `FilterParameter` class holding all filter data.
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
Assets, data objects, and documents use OpenSearch or Elasticsearch (depending on your Generic Data Index
configuration) for indexing and search.
See [Search Index Filters](./01_Extending_Search_Index_Filters.md) for implementation details.

## Listing Filters
Listing filters apply to the classic listing approach.
See [Listing Filters](./02_Extending_Listing_Filters.md) for implementation details.