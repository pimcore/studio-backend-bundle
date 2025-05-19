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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document as AssetDocument;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\DocumentFolder;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Email;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Hardlink;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Link;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Page;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type\Snippet;
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
       AssetDocument::class,
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

    public const array DOCUMENTS = [
        Document::class,
        DocumentFolder::class,
        Email::class,
        Hardlink::class,
        Link::class,
        Page::class,
        Snippet::class,
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
