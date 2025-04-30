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
