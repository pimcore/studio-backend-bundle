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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'VideoPreview',
    required: ['videoId', 'videoAvailable', 'thumbnailName', 'thumbnailStatus', 'imageThumbnail'],
    type: 'object'
)]
final class VideoPreview implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'VideoId', type: 'integer', example: 669)]
        private readonly int $videoId,
        #[Property(description: 'VideoAvailable', type: 'boolean', example: true)]
        private readonly bool $videoAvailable,
        #[Property(description: 'ThumbnailName', type: 'string', example: 'pimcore-system-treepreview')]
        private readonly string $thumbnailName,
        #[Property(description: 'Preview', type: 'string', example: '')]
        private readonly mixed $preview = null,
        #[Property(description: 'ImageThumbnail', type: 'string', example: '')]
        private readonly string $imageThumbnail,
    ) {
    }

    public function getVideoId(): int
    {
        return $this->videoId;
    }

    public function isVideoAvailable(): bool
    {
        return $this->videoAvailable;
    }

    public function getThumbnailName(): string
    {
        return $this->thumbnailName;
    }

    public function getPreviewUrl(): mixed
    {
        return $this->preview;
    }

    public function getImageThumbnail(): string
    {
        return $this->imageThumbnail;
    }
}
