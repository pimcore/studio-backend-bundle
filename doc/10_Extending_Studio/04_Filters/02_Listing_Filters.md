# Extending Listing Filters

The Listing Filters are based on the Pimcore Listing classes and provide an abstraction layer to add filters to a listing.

## Adding a new Listing Filter
In order to add a new OpenSearch Filter, you need to implement the `Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface` and register the service with the tag `pimcore.studio_backend.listing.filter`

## AbstractListing vs CallableListingInterface
Unfortunately not all listings for the same. 
If you encounter a listing that only allows you to use the method `setFilter` you have to do all the filtering in on filter like e.g. the properties filter.
For AbstractListings you can use the method `addConditionParam` to chain the filters.
Make sure that you do not override the filters of the listing by using `setCondition`.