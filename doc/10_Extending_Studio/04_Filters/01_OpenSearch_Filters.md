# Extending OpenSearch Filters

The OpenSearch Filters are based on the [Generic Data Index Bundle](https://github.com/pimcore/generic-data-index-bundle) and provide an abstraction layer to add filters to an OpenSearch query.

Currently, there are queries for assets, data objects and documents implemented.

## Adding a new OpenSearch Filter
In order to add a new OpenSearch Filter, you need to implement the `Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface` and register the service with the tag `pimcore.studio_backend.open_search.filter`#

In the apply method, you can add your custom logic to the query.
Make sure to check if your filter is applicable to the given parameters type.

## Available Search Modifiers for Queries
For a full list of the available search modifiers, please refer to the [Generic Data Index Bundle](https://github.com/pimcore/generic-data-index-bundle/tree/1.x/doc/04_Searching_For_Data_In_Index/05_Search_Modifiers.md)
