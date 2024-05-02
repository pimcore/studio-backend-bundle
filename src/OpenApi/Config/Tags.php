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
    name: Tags::Properties->name,
    description: 'Get properties for elements'
)]
#[Tag(
    name: Tags::Translation->name,
    description: 'Get translations either for a single key or multiple keys'
)]
#[Tag(
    name: Tags::Versions->name,
    description: 'Versions operations to get/list/publish/delete and cleanup versions'
)]
enum Tags
{
    case Assets;
    case Authorization;
    case DataObjects;
    case Properties;
    case Translation;
    case Versions;
}
