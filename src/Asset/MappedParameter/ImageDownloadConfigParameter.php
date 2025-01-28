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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;
use function in_array;

/**
 * @internal
 */
final readonly class ImageDownloadConfigParameter
{
    private const array ALLOWED_RESIZE_MIME_TYPES = [
        MimeTypes::JPEG->value,
        MimeTypes::PNG->value,
        MimeTypes::ORIGINAL->value,
        MimeTypes::SOURCE->value,
    ];

    public function __construct(
        private string $mimeType,
        private string $resizeMode = ResizeModes::NONE,
        private ?int $width = null,
        private ?int $height = null,
        private ?int $quality = 85,
        private ?int $dpi = null,
        private ?string $positioning = 'center',
        private bool $cover = false,
        private bool $frame = false,
        private bool $contain = false,
        private bool $forceResize = false,
        private bool $cropPercent = false,
        private ?int $cropWidth = null,
        private ?int $cropHeight = null,
        private ?int $cropTop = null,
        private ?int $cropLeft = null,
    ) {

        $this->validateResizeMode();
        $this->validateTransformations();
        $this->validateCropOptions();
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

    public function getCoverTransformation(): array
    {
        return [
            ... $this->getBaseTransformationValues(),
            'positioning' => $this->getPositioning(),
        ];
    }

    public function getFrameTransformation(): array
    {
        return $this->getBaseTransformationValues();
    }

    public function getContainTransformation(): array
    {
        return $this->getBaseTransformationValues();
    }

    public function getPositioning(): ?string
    {
        return $this->positioning;
    }

    public function getForceResize(): bool
    {
        return $this->forceResize;
    }

    public function hasCover(): bool
    {
        return $this->cover;
    }

    public function hasFrame(): bool
    {
        return $this->frame;
    }

    public function hasContain(): bool
    {
        return $this->contain;
    }

    private function isValidWidth(): bool
    {
        return $this->width !== null && $this->width > 0;
    }

    private function isValidHeight(): bool
    {
        return $this->height !== null && $this->height > 0;
    }

    private function getBaseTransformationValues(): array
    {
        return [
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'forceResize' => $this->getForceResize(),
        ];
    }

    private function validateResizeMode(): void
    {
        if ($this->resizeMode === ResizeModes::NONE) {
            return;
        }

        if (!in_array($this->mimeType, self::ALLOWED_RESIZE_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Invalid mime type' . $this->mimeType);
        }

        if ($this->resizeMode === ResizeModes::SCALE_BY_HEIGHT && !$this->isValidHeight()) {
            throw new InvalidArgumentException(
                'Height must be set and non-negative when using scale by width resize mode'
            );
        }

        if ($this->resizeMode === ResizeModes::SCALE_BY_WIDTH && !$this->isValidWidth()) {
            throw new InvalidArgumentException(
                'Width must be set and non-negative when using scale by width resize mode'
            );
        }

        if ($this->resizeMode === ResizeModes::RESIZE && (!$this->isValidWidth() || !$this->isValidHeight())) {
            throw new InvalidArgumentException(
                'Width and height must be set and non-negative when using resize'
            );
        }
    }

    private function validateTransformations(): void
    {
        if ((!$this->isValidWidth() || !$this->isValidHeight()) &&
            ($this->hasFrame() || $this->hasCover() || $this->hasContain())
        ) {
            throw new InvalidArgumentException(
                'Width, height must be set and non-negative when using frame, cover, contain or resize'
            );
        }
    }

    public function getCropHeight(): ?int
    {
        return $this->cropHeight;
    }

    public function getCropPercent(): bool
    {
        return $this->cropPercent;
    }

    public function getCropWidth(): ?int
    {
        return $this->cropWidth;
    }

    public function getCropTop(): ?int
    {
        return $this->cropTop;
    }

    public function getCropLeft(): ?int
    {
        return $this->cropLeft;
    }

    private function validateCropOptions(): void
    {
        if ($this->getCropPercent() &&
            (
                $this->getCropWidth() === null ||
                $this->getCropHeight() === null ||
                $this->getCropTop() === null ||
                $this->getCropLeft() === null
            )
        ) {
            throw new InvalidArgumentException(
                'Crop percent cannot be used without crop width, height, top and left'
            );
        }
    }
}
