# Extending Notes

Notes to log changes or events on elements independently of the versioning. You can get more general information about notes [here](https://pimcore.com/docs/platform/Pimcore/Tools_and_Features/Notes_and_Events/)

There are currently 4 predefined types of notes for each element type (asset, document, data object):
- content
- seo
- warning
- notice

You can extend or adapt these types in the `config.yaml` file, for example:

```yaml
pimcore_studio_backend:
    types:
        asset: ['content', 'seo', 'warning', 'notice', 'customType']
```
