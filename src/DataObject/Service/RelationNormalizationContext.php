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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Model\DataObject\Concrete;

/**
 * Holds the parent (owner) object currently being normalized so that nested
 * relation adapters can check save/publish permissions against it without
 * requiring the parent to be threaded through every normalize() signature.
 *
 * @internal
 */
class RelationNormalizationContext
{
    private ?Concrete $parent = null;

    public function setParent(?Concrete $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?Concrete
    {
        return $this->parent;
    }
}
