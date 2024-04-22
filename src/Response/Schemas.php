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

namespace Pimcore\Bundle\StudioApiBundle\Response;

use Pimcore\Bundle\StudioApiBundle\Response\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Video;
use Pimcore\Bundle\StudioApiBundle\Response\Schema\DevError;
use Pimcore\Bundle\StudioApiBundle\Response\Schema\Error;

/**
 * @internal
 */
final readonly class Schemas
{
    public const Assets = [
       Image::class,
       Document::class,
       Audio::class,
       Video::class,
       Archive::class,
       Text::class,
       Folder::class,
       Unknown::class,
    ];

    public const Errors = [
        Error::class,
        DevError::class
    ];
}
