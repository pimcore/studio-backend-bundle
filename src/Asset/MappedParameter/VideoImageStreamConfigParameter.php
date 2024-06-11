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

/**
 * @internal
 */
final readonly class VideoImageStreamConfigParameter
{
    public function __construct(
        private ?int $width = null,
        private ?int $height = null,
        private ?bool $aspectRatio = null,
        private ?bool $frame = null,
    )
    {
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getAspectRatio(): ?bool
    {
        return $this->aspectRatio;
    }

    public function getFrame(): ?bool
    {
        return $this->frame;
    }
}
