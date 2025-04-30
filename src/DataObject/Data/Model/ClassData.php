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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

/**
 * @internal
 */
final readonly class ClassData
{
    public function __construct(
        private bool $allowInheritance,
        private bool $allowVariants,
        private bool $showVariants,
        private bool $hasPreview,
    ) {
    }

    public function getAllowInheritance(): bool
    {
        return $this->allowInheritance;
    }

    public function getAllowVariants(): bool
    {
        return $this->allowVariants;
    }

    public function getShowVariants(): bool
    {
        return $this->showVariants;
    }

    public function getHasPreview(): bool
    {
        return $this->hasPreview;
    }
}
