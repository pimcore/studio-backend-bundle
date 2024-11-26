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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
trait ClassDataTrait
{
    #[Property(description: 'Inheritance allowed', type: 'bool', example: false)]
    private ?bool $allowInheritance = null;

    #[Property(description: 'Variants allowed', type: 'bool', example: false)]
    private ?bool $allowVariants = null;

    #[Property(description: 'Show variants', type: 'bool', example: false)]
    private ?bool $showVariants = null;

    #[Property(description: 'Has preview', type: 'bool', example: false)]
    private ?bool $hasPreview = null;

    public function getAllowInheritance(): ?bool
    {
        return $this->allowInheritance;
    }

    public function setAllowInheritance(bool $allowInheritance): void
    {
        $this->allowInheritance = $allowInheritance;
    }

    public function getAllowVariants(): ?bool
    {
        return $this->allowVariants;
    }

    public function setAllowVariants(bool $allowVariants): void
    {
        $this->allowVariants = $allowVariants;
    }

    public function getShowVariants(): ?bool
    {
        return $this->showVariants;
    }

    public function setShowVariants(bool $showVariants): void
    {
        $this->showVariants = $showVariants;
    }

    public function getHasPreview(): ?bool
    {
        return $this->hasPreview;
    }

    public function setHasPreview(bool $hasPreview): void
    {
        $this->hasPreview = $hasPreview;
    }
}
