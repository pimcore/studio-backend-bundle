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
    public function __construct(
        private int $objectId,
        private bool $inherited = false,
        private bool $inheritable = true
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

    /**
     * Whether the field takes part in inheritance at all. A field that does not is
     * reported as not inherited, which on its own is indistinguishable from a field
     * that carries an own value - clients that offer to give a field back to its
     * origin object need to tell the two apart.
     */
    public function isInheritable(): bool
    {
        return $this->inheritable;
    }
}
