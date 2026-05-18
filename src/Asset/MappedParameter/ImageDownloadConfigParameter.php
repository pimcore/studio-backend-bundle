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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;
use function in_array;

/**
 * @internal
 */
readonly class ImageDownloadConfigParameter extends StreamCropParameter
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
        bool $cropPercent = false,
        ?float $cropHeight = null,
        ?float $cropWidth = null,
        ?float $cropTop = null,
        ?float $cropLeft = null,
    ) {

        $this->validateResizeMode();
        $this->validateTransformations();

        parent::__construct($cropPercent, $cropHeight, $cropWidth, $cropTop, $cropLeft);
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
}
