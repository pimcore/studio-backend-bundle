# Extending Pimcore Studio Backend

Pimcore Studio Backend Bundle can be extended to add custom endpoints, filters, grid customizations a.m.m. 
Most of the customizations can be done by implementing interfaces and registering the services with the according tags.

The main topics that can be extended are:
- [Endpoints](01_Endpoints.md)
- [OpenApi](02_OpenApi.md)
- [Filters](03_Filters.md)
  - OpenSearch Filters
  - Listing Filters
- [Grids](#Grids)
  - Columns
  - Resolvers
  - Collectors
- [Patcher](#Patcher)
- [Updater](#Updater)
- [Additional Attributes](#Additional-Attributes)