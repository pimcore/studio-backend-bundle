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

namespace Pimcore\Model\DataObject;

/**
 * Stands in for a generated data object class without touching the database: it declares the getter of
 * its relation field itself, like the generated classes do, and lets tests set what the service reads.
 *
 * @internal
 */
class LightweightRelationTestObject extends Concrete
{
    private ?Concrete $nextParentForInheritance = null;

    private bool $lazyKeyLoaded = false;

    public function getObjects(): ?array
    {
        return null;
    }

    public function getClassName(): string
    {
        return 'LightweightRelationTestObject';
    }

    public function setNextParentForInheritance(?Concrete $parent): void
    {
        $this->nextParentForInheritance = $parent;
    }

    public function getNextParentForInheritance(): ?Concrete
    {
        return $this->nextParentForInheritance;
    }

    public function setLazyKeyLoaded(bool $lazyKeyLoaded): void
    {
        $this->lazyKeyLoaded = $lazyKeyLoaded;
    }

    public function isLazyKeyLoaded(string $key): bool
    {
        return $this->lazyKeyLoaded;
    }
}
