# Extending Pimcore Studio Backend

Pimcore Studio Backend Bundle can be extended to add custom endpoints, filters, grid customizations and more. 
Most of the customizations can be done by implementing interfaces and registering the services with the according tags.

To add your custom implementations to the API docs you need to add the following configuration to your `config.yaml`:

```yaml
pimcore_studio_backend:
    open_api_scan_paths:
        - "%kernel.project_dir%/vendor/<namespace>/<bundle-name>/src"
```

This ensures that Swagger can scan your routes for the OpenApi documentation. Keep in mind that the paths are relative to the project directory.

The main topics that can be extended are:
- [Additional and Custom Attributes](./01_Additional_and_Custom_Attributes.md)
- [Grid with Custom Columns](./02_Extending_Grid_with_Custom_Columns.md)
- [Asset Metadata Adapters](./03_Assets/01_Extending_Metadata_Adapters.md)
- [Endpoints](./04_Extending_Endpoints.md)
- [Data Object Field Definition Adapters](./05_Data_Objects/01_Field_Definition_Adapters.md)
- [OpenApi](./06_Extending_OpenApi.md)
- [Custom Document Types](./07_Documents/01_Custom_Document_Types.md)
- [Filters](./08_Extending_Filters/README.md)
  - [Search Index Filters](./08_Extending_Filters/01_Extending_Search_Index_Filters.md)
  - [Listing Filters](./08_Extending_Filters/02_Extending_Listing_Filters.md)
- [Perspectives](./09_Perspectives/README.md)
  - [Widgets](./09_Perspectives/01_Extending_Widgets.md)
- [Updater and Patcher](./10_Extending_Updater_and_Patcher.md)
- [Events](./11_Extending_via_Events.md)
- [GDPR Data Providers](./12_Extending_GDPR_Data_Providers.md)
