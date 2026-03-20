---
title: Extending Pimcore Studio Backend
description: Extension points for the Studio Backend API layer - endpoints, events, filters, grid columns, and adapters.
---

# Extending Pimcore Studio Backend

This chapter covers extension points specific to the Studio Backend API layer.
For an overview of all Pimcore extension points across core, backend, and frontend layers,
see [Extending Pimcore](https://github.com/pimcore/pimcore/blob/2026.x/doc/10_Extending_Pimcore/README.md).

Most customizations follow the same pattern: implement an interface and register the service with
the corresponding DI tag. The Studio Backend auto-discovers tagged services at runtime.

To include your custom endpoints in the OpenAPI documentation, register the scan path:

```yaml
pimcore_studio_backend:
    open_api_scan_paths:
        - "%kernel.project_dir%/vendor/<namespace>/<bundle-name>/src"
```

## Extension Points

- [Additional and Custom Attributes](./01_Additional_and_Custom_Attributes.md)
- [Custom Grid Columns](./02_Extending_Grid_with_Custom_Columns.md)
- [Asset Metadata Adapters](./03_Assets/01_Extending_Metadata_Adapters.md)
- [Custom Endpoints](./04_Extending_Endpoints.md)
- [Field Definition Adapters](./05_Data_Objects/01_Field_Definition_Adapters.md)
- [OpenAPI Schemas](./06_Extending_OpenApi.md)
- [Custom Document Types](./07_Documents/01_Custom_Document_Types.md)
- [Filters](./08_Extending_Filters/README.md)
  - [Search Index Filters](./08_Extending_Filters/01_Extending_Search_Index_Filters.md)
  - [Listing Filters](./08_Extending_Filters/02_Extending_Listing_Filters.md)
- [Perspectives and Widgets](./09_Perspectives/README.md)
  - [Custom Widget Types](./09_Perspectives/01_Extending_Widgets.md)
- [Update and Patch Adapters](./10_Extending_Updater_and_Patcher.md)
- [Events](./11_Extending_via_Events.md)
- [GDPR Data Providers](./12_Extending_GDPR_Data_Providers.md)
