<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
    name: Tags::DataObjects->value,
    description: 'tag_dataobjects_description'
)]
#[Tag(
    name: Tags::Dependencies->value,
    description: 'tag_dependencies_description'
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
    name: Tags::Mercure->value,
    description: 'tag_mercure_description'
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
    name: Tags::Properties->value,
    description: 'tag_properties_description'
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
    name: Tags::Translation->value,
    description: 'tag_translation_description'
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
    name: Tags::Workflows->value,
    description: 'tag_workflows_description'
)]
enum Tags: string
{
    case Assets = 'Assets';
    case AssetGrid = 'Asset Grid';
    case AssetThumbnails = 'Asset Thumbnails';
    case Authorization = 'Authorization';
    case DataObjects = 'Data Objects';
    case Dependencies = 'Dependencies';
    case Elements = 'Elements';
    case ExecutionEngine = 'Execution Engine';
    case Emails = 'E-Mails';
    case Mercure = 'Mercure';
    case Notes = 'Notes';
    case Notifications = 'Notifications';
    case Properties = 'Properties';
    case Role = 'Role Management';
    case Schedule = 'Schedule';
    case Settings = 'Settings';
    case Tags = 'Tags';
    case TagsForElement = 'Tags for Element';
    case Translation = 'Translation';
    case User = 'User Management';
    case Versions = 'Versions';
    case Workflows = 'Workflows';
}
