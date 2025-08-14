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
    ) {
        parent::__construct(
            $mimeType,
            $resizeMode,
            $width,
            $height,
            $quality,
            $dpi,
        );
    }

    public function getPage(): ?int
    {
        return $this->page;
    }
}
