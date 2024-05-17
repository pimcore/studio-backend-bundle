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
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Folder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\DevError;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\Error;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\ImageVersion;

/**
 * @internal
 */
final readonly class Schemas
{
    public const ASSETS = [
       Image::class,
       Document::class,
       Audio::class,
       Video::class,
       Archive::class,
       Text::class,
       Folder::class,
       Unknown::class,
    ];

    public const VERSIONS = [
        AssetVersion::class,
        ImageVersion::class,
        DataObjectVersion::class,
        DocumentVersion::class,
    ];

    public const ERRORS = [
        Error::class,
        DevError::class,
    ];
}
