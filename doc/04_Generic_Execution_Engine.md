# Generic Execution Engine
:::caution

This documentation is currently work in progress and will be updated soon.

:::

The Generic Execution Engine is a powerful tool to execute actions in the background. It is based on the Symfony Messenger component, to learn more about it, please visit the [Generic Execution Engine documentation](https://github.com/pimcore/pimcore/tree/11.x/doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine).

There are several actions, which currently take benefits of Execution Engine:

### Asset ZIP upload
When uploading a ZIP file containing assets, the ZIP file is extracted and the assets are created in the background.

### Asset ZIP Download
When downloading a multiple assets as ZIP file, the ZIP file is created in the background. This ZIP archive can then be downloaded.
There are some configuration options available for the ZIP download, which can be configured in the `config.yaml` file.

```yaml
pimcore_studio_backend:
    asset_download_settings:
        # Maximum number of assets that can be downloaded in a single ZIP file. Default value is 1000.
        amount_limit: 1000
        # Maximum size of the ZIP file in bytes. Default value is 5 GB.
        size_limit: 5368709120
```

### Asset CSV Export
Assets can be exported based on the provided grid configuration as a CSV file. The export is done in the background and can be downloaded after its finished.
There are some configuration options available for the CSV export, which can be configured in the `config.yaml` file.

```yaml
pimcore_studio_backend:
    csv_settings:
        # Default delimiter for CSV files when no value is passed by grid configuration. Default value is ','.
        default_delimiter: ','
```

### Assets deletion
Assets and folders can be deleted in the background. This is useful when deleting a large number of assets or asset trees.

### Asset cloning
Assets and folders can be cloned in the background. This is useful when cloning a large number of assets or asset trees.
