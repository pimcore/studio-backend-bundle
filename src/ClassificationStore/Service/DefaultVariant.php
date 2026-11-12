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
 * The default variant of a `multiValue` key as the key declares it: whether a value carrying no
 * variant is permitted, and the optional label of that slot.
 *
 * @internal
 */
final readonly class DefaultVariant
{
    public function __construct(
        private bool $permitted,
        private ?string $label = null,
    ) {
    }

    public function isPermitted(): bool
    {
        return $this->permitted;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
