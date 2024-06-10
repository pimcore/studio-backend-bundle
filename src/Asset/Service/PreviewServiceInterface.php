<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\VideoPreview;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidThumbnailException;
use Pimcore\Model\Asset\Video;

/**
 * @internal
 */
interface PreviewServiceInterface
{
    /**
     * @throws InvalidThumbnailException
     */
    public function getVideoPreview(
        Video $video,
        string $thumbnailName
    ): VideoPreview;
}