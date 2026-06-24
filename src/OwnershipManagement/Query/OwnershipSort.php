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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query;

/**
 * A single sort instruction (field + direction). An OwnershipListQuery carries an ordered list of them:
 * the primary sort followed by any additional sorts used as tie-breakers.
 */
final readonly class OwnershipSort
{
    public function __construct(
        private string $field,
        private string $direction,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
}
