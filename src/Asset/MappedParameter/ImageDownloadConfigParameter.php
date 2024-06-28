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

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\ResizeModes;

/**
 * @internal
 */
final readonly class ImageDownloadConfigParameter
{
    public function __construct(
        private string $mimeType,
        private string $resizeMode,
        private ?int $width = null,
        private ?int $height = null,
        private ?int $quality = null,
        private ?int $dpi = null
    ) {
        if (!\in_array($this->mimeType, MimeTypes::ALLOWED_FORMATS)) {
            throw new InvalidArgumentException('Invalid mime type' . $this->mimeType);
        }

        if (!\in_array($this->resizeMode, ResizeModes::ALLOWED_MODES)) {
            throw new InvalidArgumentException('Invalid resize mode ' . $this->resizeMode);
        }
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getResizeMode(): string
    {
        return $this->resizeMode;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function getDpi(): ?int
    {
        return $this->dpi;
    }
}
