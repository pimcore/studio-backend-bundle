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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Data\Model;

/**
 * @internal
 */
final readonly class PageSnippetData
{
    public function __construct(
        private ?string $url = null,
        private ?int $staticLastGenerated = null,
        private ?string $contentMainDocumentPath = null,
    ) {
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getStaticLastGenerated(): ?int
    {
        return $this->staticLastGenerated;
    }

    public function getContentMainDocumentPath(): ?string
    {
        return $this->contentMainDocumentPath;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
