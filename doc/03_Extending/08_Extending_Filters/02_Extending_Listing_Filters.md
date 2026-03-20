---
title: Listing Filters
description: Add custom filters for classic Pimcore listings.
---

# Extending Listing Filters

Listing Filters add filter logic to classic Pimcore Listing classes.

## Adding a Listing Filter

Implement `Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface` and register the
service with the tag `pimcore.studio_backend.listing.filter`.

## AbstractListing vs CallableListingInterface

Not all listings support the same filtering API:

- **`AbstractListing`**: Use `addConditionParam()` to chain multiple filters. Do not use
  `setCondition()` as it overwrites all existing conditions.
- **`CallableListingInterface`**: Only supports `setFilter()`, so all filtering logic must be
  combined into a single filter (see the properties filter for an example).