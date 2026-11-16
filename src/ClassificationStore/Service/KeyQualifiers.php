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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\VariantLayout;

/**
 * What a key declares about its qualified values, resolved once off one key configuration: the five
 * members a key layout publishes beside its definition. Every member is null for a key that is not
 * of type `multiValue`.
 *
 * @internal
 */
final readonly class KeyQualifiers
{
    /**
     * @param VariantLayout[]|null $variants
     */
    public function __construct(
        private ?array $variants,
        private ?bool $multiValue,
        private ?int $maximum,
        private ?bool $defaultVariant,
        private ?string $defaultVariantLabel,
    ) {
    }

    /**
     * @return VariantLayout[]|null
     */
    public function getVariants(): ?array
    {
        return $this->variants;
    }

    public function getMultiValue(): ?bool
    {
        return $this->multiValue;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    public function getDefaultVariant(): ?bool
    {
        return $this->defaultVariant;
    }

    public function getDefaultVariantLabel(): ?string
    {
        return $this->defaultVariantLabel;
    }
}
