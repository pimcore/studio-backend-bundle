# Extending Pimcore Studio Backend

Pimcore Studio Backend Bundle can be extended to add custom endpoints, filters, grid customizations a.m.m. 
Most of the customizations can be done by implementing interfaces and registering the services with the according tags.

To add your custom implementations to the API docs you need to add the following configuration to your `config.yaml`:

```yaml
pimcore_studio_backend:
    open_api_scan_paths:
        - "%kernel.project_dir%/vendor/<namespace>/<bundle-name>/src"
```

This ensures that swagger can scan your routes for the OpenApi documentation. Keep in mind that the paths are relative to the project directory.

The main topics that can be extended are:
- [Endpoints](01_Endpoints.md)
- [OpenApi](02_OpenApi.md)
- [Filters](03_Filters.md)
  - [Search Index Filters](04_Filters/01_Search_Index_Filters.md)
  - [Listing Filters](04_Filters/02_Listing_Filters.md)
- [Grids](05_Grid.md)
- [Update & Patcher](06_Update_Patch.md)
- [Additional Attributes](#Additional-Attributes)