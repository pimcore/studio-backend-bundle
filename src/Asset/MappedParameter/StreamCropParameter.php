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

/**
 * @internal
 */
readonly class StreamCropParameter
{
    public function __construct(
        private bool $cropPercent = false,
        private ?float $cropHeight = null,
        private ?float $cropWidth = null,
        private ?float $cropTop = null,
        private ?float $cropLeft = null,
    ) {
        $this->validateCropOptions();
    }

    public function getCropPercent(): bool
    {
        return $this->cropPercent;
    }


    public function getCropHeight(): ?float
    {
        return $this->cropHeight;
    }

    public function getCropWidth(): ?float
    {
        return $this->cropWidth;
    }

    public function getCropTop(): ?float
    {
        return $this->cropTop;
    }

    public function getCropLeft(): ?float
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
