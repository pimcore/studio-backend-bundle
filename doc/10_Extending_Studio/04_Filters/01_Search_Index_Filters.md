# Extending Search Index Filters

The Filters are based on the [Generic Data Index Bundle](https://github.com/pimcore/generic-data-index-bundle) and provide an abstraction layer to add filters to an OpenSearch or Elasticsearch query.

Currently, there are queries for assets, data objects and documents implemented.

## Adding a new Search Index Filter
In order to add a new filter, you need to implement the `Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface` and register the service with the tag `pimcore.studio_backend.search_index.filter`

## Available Search Modifiers for Queries
For a full list of the available search modifiers, please refer to the [Generic Data Index Bundle](https://github.com/pimcore/generic-data-index-bundle/tree/1.x/doc/04_Searching_For_Data_In_Index/05_Search_Modifiers.md)


#### Example

```php
<?php
declare(strict_types=1);

namespace App\MyBundle\Filter;;

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