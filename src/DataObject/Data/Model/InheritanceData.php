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
final readonly class InheritanceData
{
    /**
     * @param int $objectId id of the object the current value originates from
     * @param bool $inherited whether the current value comes from an ancestor
     * @param bool $inheritable whether the field type can take part in inheritance at all
     * @param mixed $inheritedValue the normalized value the field inherits (or would inherit) from the nearest
     *                              ancestor holding a value; null when no ancestor holds one or when it was
     *                              not requested (see FieldContextData::shouldResolveInheritedValue())
     */
    public function __construct(
        private int $objectId,
        private bool $inherited = false,
        private bool $inheritable = true,
        private mixed $inheritedValue = null
    ) {
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function isInherited(): bool
    {
        return $this->inherited;
    }

    public function isInheritable(): bool
    {
        return $this->inheritable;
    }

    public function getInheritedValue(): mixed
    {
        return $this->inheritedValue;
    }
}
