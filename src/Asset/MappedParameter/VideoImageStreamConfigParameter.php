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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;

/**
 * @internal
 */
final readonly class VideoImageStreamConfigParameter
{
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private bool $aspectRatio = false,
        private bool $frame = false,
        private bool $async = false,
    ) {
        if ($this->frame && ($this->width === null || $this->height === null)) {
            throw new InvalidThumbnailConfigurationException(
                'Width and height must be set when using frame configuration'
            );
        }
        if ($this->aspectRatio && $this->width === null) {
            throw new InvalidThumbnailConfigurationException(
                'Width must be set when using aspectRatio configuration'
            );
        }
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getAspectRatio(): bool
    {
        return $this->aspectRatio;
    }

    public function getFrame(): bool
    {
        return $this->frame;
    }

    public function getAsync(): bool
    {
        return $this->async;
    }
}
