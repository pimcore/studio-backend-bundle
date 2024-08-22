# Extending Filters

Currently, there are two different filters systems implemented. The OpenSearch Filters and the Listing Filters.
The main difference lies in the implementation of the adapters and the tag.

The idea is that every filter knows for which type it is responsible and can be used in the according context.


## How does it work
Every filter type has its own filter service class which loads all the tagged services and iterates over them and calls the `apply` method.
The filter itself is responsible for the logic and if the filter is applied.
The input parameters can be different depending on the filter type.
Keep in mind that for the listing filters only supported filters are loaded based on the listing itself.

### Query Filters with Mapped Parameters
In this example, the query parameters are mapped via `#[MapQueryString]` into the request object, which is then used in the filter itself.

```php
<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter;

interface CollectionParametersInterface
{
    public function getPage(): int;

    public function getPageSize(): int;
}


```

```php
<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParametersInterface;

/**
 * @internal
 */
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

Note that every query parameter could also be mapped to a payload. In this case a mapper can be implemented [#LINK TO MAPPER]

```php
<?php
declare(strict_types=1);


namespace Pimcore\Bundle\StudioBackendBundle\Listing\Filter;

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


## OpenSearch Filters
For assets, data objects and documents we are using OpenSearch to index the data and to provide a fast search.
For more details on how to implement the OpenSearch filters see the [OpenSearch Filters](04_Filters/01_OpenSearch_Filters.md).

## Listing Filters
For the classic approach of using listings, the Listing Filters are used.
For more details on how to implement the Listing filters see the [Listing_FILTER](#).