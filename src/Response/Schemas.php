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

use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Archive;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Audio;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Document;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Folder;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Image;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Text;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Video;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\DevError;
use Pimcore\Bundle\StudioBackendBundle\Response\Schema\Error;

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

    public const ERRORS = [
        Error::class,
        DevError::class,
    ];
}
