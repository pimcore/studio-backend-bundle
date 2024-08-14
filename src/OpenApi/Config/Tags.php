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
    name: Tags::Assets->name,
    description: 'tag_assets_description',
)]
#[Tag(
    name: Tags::AssetThumbnails->name,
    description: 'tag_asset_thumbnail_description'
)]
#[Tag(
    name: Tags::Authorization->name,
    description: 'tag_authorization_description'
)]
#[Tag(
    name: Tags::DataObjects->name,
    description: 'tag_dataobjects_description'
)]
#[Tag(
    name: Tags::Dependencies->name,
    description: 'tag_dependencies_description'
)]
#[Tag(
    name: Tags::Elements->name,
    description: 'tag_elements_description'
)]
#[Tag(
    name: Tags::ExecutionEngine->name,
    description: 'tag_execution_engine_description'
)]
#[Tag(
    name: Tags::Emails->value,
    description: 'tag_emails_description'
)]
#[Tag(
    name: Tags::Grid->name,
    description: 'tag_grid_description'
)]
#[Tag(
    name: Tags::Mercure->name,
    description: 'tag_mercure_description'
)]
#[Tag(
    name: Tags::Notes->name,
    description: 'tag_notes_description'
)]
#[Tag(
    name: Tags::Notifications->name,
    description: 'tag_notifications_description'
)]
#[Tag(
    name: Tags::Properties->name,
    description: 'tag_properties_description'
)]
#[Tag(
    name: Tags::Role->value,
    description: 'tag_role_description'
)]
#[Tag(
    name: Tags::Schedule->name,
    description: 'tag_schedule_description'
)]
#[Tag(
    name: Tags::Settings->name,
    description: 'tag_settings_description'
)]
#[Tag(
    name: Tags::Tags->name,
    description: 'tag_tags_description'
)]
#[Tag(
    name: Tags::TagsForElement->value,
    description: 'tag_tags_for_element_description'
)]
#[Tag(
    name: Tags::Translation->name,
    description: 'tag_translation_description'
)]
#[Tag(
    name: Tags::User->value,
    description: 'tag_user_description'
)]
#[Tag(
    name: Tags::Versions->name,
    description: 'tag_versions_description'
)]
#[Tag(
    name: Tags::Workflows->name,
    description: 'tag_workflows_description'
)]
enum Tags: string
{
    case Assets = 'Assets';
    case AssetThumbnails = 'Asset Thumbnails';
    case Authorization = 'Authorization';
    case DataObjects = 'DataObjects';
    case Dependencies = 'Dependencies';
    case Elements = 'Elements';
    case ExecutionEngine = 'Execution Engine';
    case Emails = 'E-Mails';
    case Grid = 'Grid';
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
