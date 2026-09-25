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

### Foundational Patterns

- [Events](./01_Extending_via_Events.md)
- [Additional and Custom Attributes](./02_Additional_and_Custom_Attributes.md)
- [Custom Endpoints](./03_Extending_Endpoints.md)
- [OpenAPI Schemas](./04_Extending_OpenApi.md)

### Element-Specific Extensions

- [Asset Metadata Adapters](./05_Assets/01_Extending_Metadata_Adapters.md)
- [Field Definition Adapters](./06_Data_Objects/01_Field_Definition_Adapters.md)
- [Custom Document Types](./07_Documents/01_Custom_Document_Types.md)

### Cross-Cutting Concerns

- [Custom Grid Columns](./08_Extending_Grid_with_Custom_Columns.md)
- [Filters](./09_Extending_Filters/README.md)
  - [Search Index Filters](./09_Extending_Filters/01_Extending_Search_Index_Filters.md)
  - [Listing Filters](./09_Extending_Filters/02_Extending_Listing_Filters.md)
- [Update and Patch Adapters](./10_Extending_Updater_and_Patcher.md)
- [Notifications](./14_Extending_Notifications.md)

### UI and Specialized

- [Perspectives and Widgets](./11_Perspectives/README.md)
  - [Custom Widget Types](./11_Perspectives/01_Extending_Widgets.md)
- [GDPR Data Providers](./12_Extending_GDPR_Data_Providers.md)
- [Ownership Management Providers](./13_Extending_Ownership_Management.md)
