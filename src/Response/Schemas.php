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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DataObjectContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\DocumentContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\DevError;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\Error;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\AssetSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DataObjectSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DocumentSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;

/**
 * @internal
 */
final readonly class Schemas
{
    public const array ASSETS = [
       Image::class,
       Document::class,
       Audio::class,
       Video::class,
       Archive::class,
       Text::class,
       AssetFolder::class,
       Unknown::class,
    ];

    public const array DATA_OBJECTS = [
        DataObject::class,
        DataObjectFolder::class,
    ];

    public const array ELEMENT_CONTEXT_PERMISSIONS = [
        AssetContextPermissions::class,
        DataObjectContextPermissions::class,
        DocumentContextPermissions::class,
    ];

    public const array ERRORS = [
        Error::class,
        DevError::class,
    ];

    public const array SEARCH_PREVIEWS = [
        AssetSearchPreview::class,
        DataObjectSearchPreview::class,
        DocumentSearchPreview::class,
    ];

    public const array VERSIONS = [
        AssetVersion::class,
        DataObjectVersion::class,
        DocumentVersion::class,
    ];
}
