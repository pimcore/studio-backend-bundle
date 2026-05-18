---
title: Generic Execution Engine
description: Background task processing for bulk operations, exports, and asynchronous jobs.
---

# Generic Execution Engine
:::caution

This documentation is currently work in progress and will be updated soon.

Messages are dispatched via `pimcore_generic_execution_engine` transport. Please ensure you have workers processing this transport.

:::

The Generic Execution Engine executes long-running actions in the background using the Symfony Messenger
component. For the core framework documentation, see the
[Generic Execution Engine](https://github.com/pimcore/pimcore/blob/2026.x/doc/09_Development_Tools/01_Generic_Execution_Engine/README.md).

The Studio Backend uses the Execution Engine for these operations:

### Asset ZIP Upload
When uploading a ZIP file containing assets, the ZIP file is extracted and the assets are created in the background.

### Asset ZIP Download
When downloading a multiple assets as a ZIP file, this ZIP file is created in the background and can then be downloaded once the processing is finished.
There are some configuration options available for the ZIP download, which can be configured in the `config.yaml` file.

```yaml
pimcore_studio_backend:
    asset_download_settings:
        # Maximum number of assets that can be downloaded in a single ZIP file. Default value is 1000.
        amount_limit: 1000
        # Maximum size of the ZIP file in bytes. Default value is 5 GB.
        size_limit: 5368709120
```

### Element CSV Export
Elements like Assets and Data Objects can be exported based on the provided grid configuration as a CSV file. The export is done in the background and can be downloaded after it's finished.
There are some configuration options available for the CSV export, which can be configured in the `config.yaml` file.

### Element XLSX Export
Elements like Assets and Data Objects can be exported based on the provided grid configuration as a XLSX file. The export is done in the background and can be downloaded after it's finished.

```yaml
pimcore_studio_backend:
    csv_settings:
        # Default delimiter for CSV files when no value is passed by grid configuration. Default value is ';'.
        default_delimiter: ';'
```

### Elements Cloning
Assets, Documents, Data Objects and folders can be cloned in the background. This is useful when cloning a large number of elements or large trees.

### Elements Deletion
Assets, Documents, Data Objects and folders can be deleted in the background. This is useful when deleting a large number of elements or nested trees.

### Elements Recycle Bin
Before deleting elements, they and the respective children are moved to the recycle bin. This is done in the background while using the Generic Execution Engine.
By default, the recycle bin is only used for elements with fewer than 100 children. Configure the threshold in `config.yaml`:

```yaml
pimcore_studio_backend:
    element_recycle_bin_threshold: 100
```

### Elements Patching
Patching of multiple elements is executed with the help of Generic Execution Engine in the background. This is useful when using bulk patches on a large number of elements.

### Elements Rewrite References
When cloning elements, the references to other elements can be rewritten. When this is desired, the action is executed in the background. This is useful when cloning a large number of elements or large trees.