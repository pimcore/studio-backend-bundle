<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Config;

use OpenApi\Attributes\Tag;

/**
 * @internal
 */
#[Tag(
    name: Tags::Assets->value,
    description: 'tag_assets_description',
)]
#[Tag(
    name: Tags::AssetGrid->value,
    description: 'tag_asset_grid_description'
)]
#[Tag(
    name: Tags::AssetThumbnails->value,
    description: 'tag_asset_thumbnail_description'
)]
#[Tag(
    name: Tags::Authorization->value,
    description: 'tag_authorization_description'
)]
#[Tag(
    name: Tags::Cache->value,
    description: 'tag_cache_description',
)]
#[Tag(
    name: Tags::ClassDefinition->value,
    description: 'tag_class_description'
)]
#[Tag(
    name: Tags::ClassificationStore->value,
    description: 'tag_classification_store'
)]
#[Tag(
    name: Tags::DataObjects->value,
    description: 'tag_dataobjects_description'
)]
#[Tag(
    name: Tags::DataObjectsGrid->value,
    description: 'tag_dataobject_grid_description'
)]
#[Tag(
    name: Tags::Dependencies->value,
    description: 'tag_dependencies_description'
)]
#[Tag(
    name: Tags::Documents->value,
    description: 'tag_documents_description'
)]
#[Tag(
    name: Tags::Elements->value,
    description: 'tag_elements_description'
)]
#[Tag(
    name: Tags::ExecutionEngine->value,
    description: 'tag_execution_engine_description'
)]
#[Tag(
    name: Tags::Emails->value,
    description: 'tag_emails_description'
)]
#[Tag(
    name: Tags::Export->value,
    description: 'tag_export_description'
)]
#[Tag(
    name: Tags::GDPR->value,
    description: 'tag_gdpr_description'
)]
#[Tag(
    name: Tags::Mercure->value,
    description: 'tag_mercure_description'
)]
#[Tag(
    name: Tags::Metadata->value,
    description: 'tag_metadata_description'
)]
#[Tag(
    name: Tags::Notes->value,
    description: 'tag_notes_description'
)]
#[Tag(
    name: Tags::Notifications->value,
    description: 'tag_notifications_description'
)]
#[Tag(
    name: Tags::OwnershipManagement->value,
    description: 'tag_ownership_management_description'
)]
#[Tag(
    name: Tags::Perspectives->value,
    description: 'tag_perspectives_description'
)]
#[Tag(
    name: Tags::Properties->value,
    description: 'tag_properties_description'
)]
#[Tag(
    name: Tags::RecycleBin->value,
    description: 'tag_recycle_bin_description'
)]
#[Tag(
    name: Tags::Role->value,
    description: 'tag_role_description'
)]
#[Tag(
    name: Tags::Schedule->value,
    description: 'tag_schedule_description'
)]
#[Tag(
    name: Tags::Search->value,
    description: 'tag_search_description'
)]
#[Tag(
    name: Tags::Settings->value,
    description: 'tag_settings_description'
)]
#[Tag(
    name: Tags::Tags->value,
    description: 'tag_tags_description'
)]
#[Tag(
    name: Tags::TagsForElement->value,
    description: 'tag_tags_for_element_description'
)]
#[Tag(
    name: Tags::Telemetry->value,
    description: 'tag_telemetry_description'
)]
#[Tag(
    name: Tags::Translation->value,
    description: 'tag_translation_description'
)]
#[Tag(
    name: Tags::Units->value,
    description: 'tag_units_description'
)]
#[Tag(
    name: Tags::User->value,
    description: 'tag_user_description'
)]
#[Tag(
    name: Tags::Versions->value,
    description: 'tag_versions_description'
)]
#[Tag(
    name: Tags::WebsiteSettings->value,
    description: 'tag_website_settings_description'
)]
#[Tag(
    name: Tags::Workflows->value,
    description: 'tag_workflows_description'
)]
#[Tag(
    name: Tags::BundleApplicationLogger->value,
    description: 'tag_bundle_application_logger_description'
)]
#[Tag(
    name: Tags::BundleCustomReports->value,
    description: 'tag_bundle_custom_reports_description'
)]
#[Tag(
    name: Tags::BundleSeo->value,
    description: 'tag_bundle_seo_description'
)]
enum Tags: string
{
    case Assets = 'Assets';
    case AssetGrid = 'Asset Grid';
    case AssetThumbnails = 'Asset Thumbnails';
    case Authorization = 'Authorization';
    case BundleApplicationLogger = 'Bundle Application Logger';
    case BundleCustomReports = 'Bundle Custom Reports';
    case BundleSeo = 'Bundle Seo';
    case Cache = 'Cache';
    case ClassDefinition = 'Class Definition';
    case ClassificationStore = 'Classification Store';
    case DataObjects = 'Data Objects';
    case DataObjectsGrid = 'Data Object Grid';
    case Dependencies = 'Dependencies';
    case Documents = 'Documents';
    case Elements = 'Elements';
    case ExecutionEngine = 'Execution Engine';
    case Emails = 'E-Mails';
    case Export = 'Export';
    case GDPR = 'GDPR Data Extractor';
    case Mercure = 'Mercure';
    case Metadata = 'Metadata';
    case Notes = 'Notes';
    case Notifications = 'Notifications';
    case OwnershipManagement = 'Ownership Management';
    case Perspectives = 'Perspectives';
    case Properties = 'Properties';
    case RecycleBin = 'Recycle Bin';
    case Role = 'Role Management';
    case Search = 'Search';
    case Schedule = 'Schedule';
    case Settings = 'Settings';
    case SettingsAdmin = 'Settings Admin';
    case Tags = 'Tags';
    case TagsForElement = 'Tags for Element';
    case Telemetry = 'Telemetry';
    case Translation = 'Translation';
    case Units = 'Units';
    case User = 'User Management';
    case Versions = 'Versions';
    case WebsiteSettings = 'Website Settings';
    case Workflows = 'Workflows';
}
