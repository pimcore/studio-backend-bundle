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
    name: Tags::Authorization->name,
    description: 'Login via username and password to get a token or refresh the token'
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
    name: Tags::Notes->name,
    description: 'Note operations to get/list/delete notes for an element'
)]
#[Tag(
    name: Tags::Properties->name,
    description: 'Property operations to get/update/create/delete properties'
)]
#[Tag(
    name: Tags::PropertiesForElement->value,
    description: 'Property operations to get/update properties for an element'
)]
#[Tag(
    name: Tags::Translation->name,
    description: 'Get translations either for a single key or multiple keys'
)]
#[Tag(
    name: Tags::Versions->name,
    description: 'Versions operations to get/list/publish/delete and cleanup versions'
)]
enum Tags: string
{
    case Assets = 'Assets';
    case Authorization = 'Authorization';
    case DataObjects = 'DataObjects';
    case Dependencies = 'Dependencies';
    case Notes = 'Notes';
    case Properties = 'Properties';
    case PropertiesForElement = 'Properties for Element';
    case Translation = 'Translation';
    case Versions = 'Versions';
}
