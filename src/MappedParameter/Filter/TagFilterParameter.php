<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter;

/**
 * @internal
 */
final readonly class TagFilterParameter
{
    public function __construct(
        private array $tags,
        private bool $considerChildTags
    )
    {
    }

    /**
     * @return array<int>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function considerChildTags(): bool
    {
        return $this->considerChildTags;
    }
}