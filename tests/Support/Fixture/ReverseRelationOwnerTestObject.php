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
 * Stands in for the generated class of an object that owns a forward relation targeted by a
 * ReverseObjectRelation: it declares the getter/setter pair of its relation field like the
 * generated classes do and records saves instead of touching the database.
 *
 * @internal
 */
class ReverseRelationOwnerTestObject extends Concrete
{
    private mixed $publisher = null;

    private int $saveCount = 0;

    public function getPublisher(): mixed
    {
        return $this->publisher;
    }

    public function setPublisher(mixed $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function save(array $parameters = []): static
    {
        $this->saveCount++;

        return $this;
    }

    public function getSaveCount(): int
    {
        return $this->saveCount;
    }
}
