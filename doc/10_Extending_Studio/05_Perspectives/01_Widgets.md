# Extending Widgets

Widgets are the main building blocks of the perspectives. They can be used to create unique views for the Pimcore Studio.

## How to add a custom widget type

To add a custom widget to the Pimcore Studio Backend, you first need to extend the symfony configuration like the following:

### Example

```yaml
pimcore_studio_backend:
    widget_types:
      - my_custom_widget_id
```