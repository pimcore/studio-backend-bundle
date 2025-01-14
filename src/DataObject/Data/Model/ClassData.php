<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
