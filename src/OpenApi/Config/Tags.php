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
    description: 'Asset operations to get/update/create/delete assets'
)]
#[Tag(
    name: Tags::AssetThumbnails->name,
    description: 'List thumbnails for assets like videos and images'
)]
#[Tag(
    name: Tags::Authorization->name,
    description: 'Session-based login via username and password or logout and invalidate the session'
)]
#[Tag(
    name: Tags::DataObjects->name,
    description: 'DataObject operations to get/update/create/delete data objects'
)]
#[Tag(
    name: Tags::Dependencies->name,
    description: 'Get dependencies for a single element.'
)]
#[Tag(
    name: Tags::Elements->name,
    description: 'Get element properties for a single element based on its type and provided parameters.'
)]
#[Tag(
    name: Tags::ExecutionEngine->name,
    description: 'Execution engine operations related to job runs.'
)]
#[Tag(
    name: Tags::Emails->value,
    description: 'Email operations to get/update/create/delete/test emails and email blocklist.'
)]
#[Tag(
    name: Tags::Grid->name,
    description: 'Grid operations'
)]
#[Tag(
    name: Tags::Mercure->name,
    description: 'Retrieve JWT token for Mercure hub as cookie'
)]
#[Tag(
    name: Tags::Notes->name,
    description: 'Note operations to list/delete notes'
)]
#[Tag(
    name: Tags::Properties->name,
    description: 'Property operations to get/update/create/delete properties'
)]
#[Tag(
    name: Tags::Tags->name,
    description: 'Tag operations to get/list/create/update/delete tags'
)]
#[Tag(
    name: Tags::TagsForElement->value,
    description: 'Tag operations to get tags for an element'
)]
#[Tag(
    name: Tags::Translation->name,
    description: 'Get translations either for a single key or multiple keys'
)]
#[Tag(
    name: Tags::Schedule->name,
    description: 'Get schedules for an element'
)]
#[Tag(
    name: Tags::Settings->name,
    description: 'Get Settings'
)]
#[Tag(
    name: Tags::Translation->name,
    description: 'Get translations either for a single key or multiple keys'
)]
#[Tag(
    name: Tags::User->value,
    description: 'User Management operations'
)]
#[Tag(
    name: Tags::Role->value,
    description: 'Role Management operations'
)]
#[Tag(
    name: Tags::Versions->name,
    description: 'Versions operations to get/list/publish/delete and cleanup versions'
)]
#[Tag(
    name: Tags::Workflows->name,
    description: 'Workflows operations to get element workflow details'
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
    case Properties = 'Properties';
    case Schedule = 'Schedule';
    case Settings = 'Settings';
    case Tags = 'Tags';
    case TagsForElement = 'Tags for Element';
    case Translation = 'Translation';
    case User = 'User Management';
    case Role = 'Role Management';
    case Versions = 'Versions';
    case Workflows = 'Workflows';
}
