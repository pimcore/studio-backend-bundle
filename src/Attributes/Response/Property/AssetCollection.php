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

namespace Pimcore\Bundle\StudioApiBundle\Attributes\Response\Property;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Video;

/**
 * @internal
 */
final class AssetCollection extends Property
{
    public function __construct()
    {
        parent::__construct(
            'items',
            title: 'items',
            type: 'array',
            items: new Items(
                anyOf: [
                    new Schema(ref: Image::class),
                    new Schema(ref: Document::class),
                    new Schema(ref: Audio::class),
                    new Schema(ref: Video::class),
                    new Schema(ref: Archive::class),
                    new Schema(ref: Text::class),
                    new Schema(ref: Folder::class),
                    new Schema(ref: Unknown::class),
                ]
            ));
    }
}
