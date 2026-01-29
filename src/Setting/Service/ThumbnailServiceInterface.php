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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

interface ThumbnailServiceInterface
{
    /**
     * Returns true if the image adapter is valid (Imagick).
     * Returns false if using GD (fallback with less quality).
     */
    public function isImageAdapterValid(): bool;

    /**
     * Returns true if video processing is available (FFmpeg configured).
     * Returns false if video processing is not configured.
     */
    public function isVideoAdapterValid(): bool;
}
