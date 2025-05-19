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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter;

/**
 * @internal
 */
final readonly class BatchCollectionParameters
{
    public function __construct(
        private string $type,
        private array $elementIds = [],
        private array $tagIds = []
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getElementIds(): array
    {
        return $this->elementIds;
    }

    public function getTagIds(): array
    {
        return $this->tagIds;
    }
}
