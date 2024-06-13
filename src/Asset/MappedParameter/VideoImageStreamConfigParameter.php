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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidThumbnailConfigurationException;

/**
 * @internal
 */
final readonly class VideoImageStreamConfigParameter
{
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private ?string $aspectRatio = null,
        private ?string $frame = null,
        private ?string $async = null,
    )
    {
        if ($this->frame === 'true' && ($this->width === null || $this->height === null)) {
            throw new InvalidThumbnailConfigurationException(
                'Width and height must be set when using frame configuration'
            );
        }
        if ($this->aspectRatio === 'true' && $this->width === null) {
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
        return $this->aspectRatio === 'true'; // TODO: symfony 7.1 will support bool type
    }

    public function getFrame(): bool
    {
        return $this->frame === 'true'; // TODO: symfony 7.1 will support bool type
    }

    public function getAsync(): bool
    {
        return $this->async === 'true'; // TODO: symfony 7.1 will support bool type
    }
}
