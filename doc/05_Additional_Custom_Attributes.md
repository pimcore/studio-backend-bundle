# Additional and Custom Attributes

Pimcore Studio Backend allows you to add additional data to response schemas.
Every response schema implements the `AdditionalAttributesInterface` and `AdditionalAttributesTrait` which allows you to add additional attributes to the schema.

Similarly to the additional attributes, you can also add custom attributes to the schema. These attributes contain mainly data used for the `tree` customization. 
Therefore as default, the custom attributes are available for the tree response schema. 
If you want to add custom attributes to another schema, you need to implement the `CustomAttributesTrait` in the schema.

## How to add additional and custom attributes
You need to register a subscriber to that specific schema where you can add the additional data.

Every schema implements its own event that you can subscribe to.
Every event implements the `AbstractPreResponseEvent` which allows to add the actual data, but also makes it possible to get the actual Schema out of the event with a type safe getter.

## Listen to the event
For an example see [Extending Events](./10_Extending_Studio/08_Events.md). 

#### Example Event
```php
<?php
declare(strict_types=1);

namespace App\Asset\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class AssetEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.asset';

    public function __construct(
        private readonly Asset $asset
    ) {
        parent::__construct($asset);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getCustomAttributes(): CustomAttributes
    {
        return $this->asset->getCustomAttributes();
    }

    public function setCustomAttributes(CustomAttributes $customAttributes): void
    {
        $this->asset->setCustomAttributes($customAttributes);
    }
}
```

### List of custom attributes

- `icon` - The custom icon that should be displayed in the tree.
- `tooltip`- The custom HTML tooltip to be displayed in the tree.
- `additionalIcons` - Array of additional icons that should be displayed in the tree.
- `key` - The key that should be displayed in the tree.
- `additionalCssClasses` - Additional CSS classes that should be added to the tree element.

### List of available events
- `asset.delete`
- `data_object.delete`
- `document.delete`
- `pre_response.all_layouts.collection`
- `pre_response.asset`
- `pre_response.asset_custom_metadata`
- `pre_response.asset_custom_settings`
- `pre_response.asset_predefined_metadata`
- `pre_response.asset_type`
- `pre_response.asset_version`
- `pre_response.asset.video_type`
- `pre_response.bundle_application_logger.list`
- `pre_response.bundle_seo.redirect.import_stats`
- `pre_response.bundle_seo.redirect.list`
- `pre_response.bundle_seo.redirect.status`
- `pre_response.bundle_seo.robots_txt_config`
- `pre_response.class.bulk_export_available_item`
- `pre_response.class.bulk_import_prepare`
- `pre_response.class.bulk_import_result_item`
- `pre_response.class_definition`
- `pre_response.class_definition.visible_field`
- `pre_response.class_definition.collection`
- `pre_response.class_definition.folder.collection`
- `pre_response.class_definition.identifier_data`
- `pre_response.class_definition.object_brick_data`
- `pre_response.class_definition.tree`
- `pre_response.class_field_by_type`
- `pre_response.classification_store.collection`
- `pre_response.classification_store.config_collection`
- `pre_response.classification_store.configuration.collection`
- `pre_response.classification_store.configuration.collection_relation`
- `pre_response.classification_store.configuration.get_page`
- `pre_response.classification_store.configuration.group`
- `pre_response.classification_store.configuration.key`
- `pre_response.classification_store.configuration.key_group_relation`
- `pre_response.classification_store.configuration.store`
- `pre_response.classification_store.configuration.store_tree_node`
- `pre_response.classification_store.group`
- `pre_response.classification_store.group_layout`
- `pre_response.classification_store.key_group_relation`
- `pre_response.custom_layout`
- `pre_response.custom_layout.collection`
- `pre_response.custom_layout.identifier_data`
- `pre_response.custom_report_chart_data`
- `pre_response.custom_report_report`
- `pre_response.custom_report_tree_config_node`
- `pre_response.custom_report_tree_node`
- `pre_response.custom_report.column_information`
- `pre_response.data_object`
- `pre_response.data_object_detail`
- `pre_response.data_object.dynamic_select_option`
- `pre_response.data_object.formated_path`
- `pre_response.data_object.layout`
- `pre_response.data_object_version`
- `pre_response.dependency`
- `pre_response.document`
- `pre_response.document_type`
- `pre_response.document.doc_type`
- `pre_response.document.doc_type.type`
- `pre_response.document.get_translations`
- `pre_response.document.get_translation;parent`
- `pre_response.document.list_available_controllers`
- `pre_response.document.list_available_templates`
- `pre_response.document.page-snippet.render-area-block-editmode`
- `pre_response.document.site.detail`
- `pre_response.document.sites_list_available`
- `pre_response.document_version`
- `pre_response.email.blocklist.entry`
- `pre_response.email.log.detail`
- `pre_response.email.log.detail.params`
- `pre_response.email.logList.entry`
- `pre_response.element.context_permissions`
- `pre_response.element_locate`
- `pre_response.element_property`
- `pre_response.element_subtype`
- `pre_response.execution_engine.list_running_job_runs`
- `pre_response.field_collection.config`
- `pre_response.field_collection.config_layout_definition`
- `pre_response.field_collection.detail`
- `pre_response.field_collection.layout_definition`
- `pre_response.field_collection.tree`
- `pre_response.field_collection.usage_data`
- `pre_response.grid_column_configuration`
- `pre_response.grid_column_data`
- `pre_response.grid_configuration`
- `pre_response.grid_detailed_configuration`
- `pre_response.list_thumbnail`
- `pre_response.notification`
- `pre_response.notification.list.item`
- `pre_response.note`
- `pre_response.note.type`
- `pre_response.objectBrick.config`
- `pre_response.objectBrick.config_layout_definition`
- `pre_response.objectBrick.detail`
- `pre_response.objectBrick.layout_definition`
- `pre_response.objectBrick.tree`
- `pre_response.objectBrick.usage_data`
- `pre_response.perspective.config.get`
- `pre_response.perspective.widget.config.get`
- `pre_response.perspective.widget.type`
- `pre_response.predefined_property`
- `pre_response.quantity_value.unit.conversion_collection`
- `pre_response.quantity_value.unit_list`
- `pre_response.recycle_bin.item`
- `pre_response.role_tree_node`
- `pre_response.schedule`
- `pre_response.schedule.action_type`
- `pre_response.select_option.detail`
- `pre_response.select_option.tree`
- `pre_response.select_option.usage_item`
- `pre_response.settings.active_bundle`
- `pre_response.settings.available_country`
- `pre_response.simple_search.preview`
- `pre_response.simple_search.result`
- `pre_response.simple_user`
- `pre_response.tag`
- `pre_response.thumbnail.image_config`
- `pre_response.thumbnail.image_config_detail`
- `pre_response.thumbnail.image_folder`
- `pre_response.thumbnail.video_config`
- `pre_response.thumbnail.video_config_detail`
- `pre_response.thumbnail.video_folder`
- `pre_response.translations`
- `pre_response.translations.import.csv-settings`
- `pre_response.user`
- `pre_response.user_detailed_role`
- `pre_response.user_information`
- `pre_response.user_permission`
- `pre_response.user_simple_role`
- `pre_response.user_tree_node`
- `pre_response.version`
- `pre_response.website_settings.item`
- `pre_response.workflow_details`
- `pre_response.notification_recipient`
- `pre_response.php_code_transformer`
- `pre_response.data_provider`
- `pre_response.gdpr_data_row`
- `pre_response.data_provider`
- `pre_response.element.usage.item`
- `pre_response.element.usage`
