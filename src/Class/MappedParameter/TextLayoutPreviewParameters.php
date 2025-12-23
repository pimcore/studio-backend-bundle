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

namespace Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter;

/**
 * @internal
 */
final readonly class TextLayoutPreviewParameters
{
    public function __construct(
        private string $className,
        private ?string $path = null,
        private ?string $renderingData = null,
        private ?string $renderingClass = null,
        private ?string $html = null
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getRenderingData(): ?string
    {
        return $this->renderingData;
    }

    public function getRenderingClass(): ?string
    {
        return $this->renderingClass;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }
}

