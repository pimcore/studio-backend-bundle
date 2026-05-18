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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;

/**
 * @internal
 */
final readonly class DocumentImageDownloadConfigParameter extends ImageDownloadConfigParameter
{
    public function __construct(
        string $mimeType,
        private ?int $page = null,
        string $resizeMode = ResizeModes::NONE,
        ?int $width = null,
        ?int $height = null,
        ?int $quality = 85,
        ?int $dpi = null,
        bool $cropPercent = false,
        ?float $cropHeight = null,
        ?float $cropWidth = null,
        ?float $cropTop = null,
        ?float $cropLeft = null,
    ) {
        parent::__construct(
            mimeType: $mimeType,
            resizeMode: $resizeMode,
            width: $width,
            height: $height,
            quality: $quality,
            dpi: $dpi,
            cropPercent: $cropPercent,
            cropHeight: $cropHeight,
            cropWidth: $cropWidth,
            cropTop: $cropTop,
            cropLeft: $cropLeft,
        );
    }

    public function getPage(): ?int
    {
        return $this->page;
    }
}
