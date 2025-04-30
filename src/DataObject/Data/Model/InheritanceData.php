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
        private bool $inherited = false
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
}
