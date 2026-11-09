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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

/**
 * One value of a key, addressed by the qualifiers it carries inside its (group, key, language) slot.
 *
 * A variant of null is the default variant and a position of 0 is the first position, so a value
 * that carries neither qualifier addresses the same slot core's single-value path addresses.
 *
 * @internal
 */
final readonly class QualifiedValue
{
    public function __construct(
        private mixed $value,
        private ?string $variant = null,
        private int $position = 0,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * The identity of the slot this value addresses, which is the pair and nothing else - the
     * language and the key are the bucket it already sits in. The position leads and is always an
     * integer, so everything after the separator is the variant name verbatim and no two distinct
     * pairs can spell the same identity. The default variant spells the empty name, which no
     * declared variant can be: {@see KeyQualifierResolverInterface::getVariants()} leaves an empty
     * name out of the declaration.
     */
    public function getQualifier(): string
    {
        return $this->position . ':' . ($this->variant ?? '');
    }

    public function withValue(mixed $value): self
    {
        return new self($value, $this->variant, $this->position);
    }
}
