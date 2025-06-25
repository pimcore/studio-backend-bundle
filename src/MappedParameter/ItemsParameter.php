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

namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final readonly class ItemsParameter
{
    /**
     * @param array<int> $items
     */
    public function __construct(
        private array $items
    ) {
        if (empty($this->items)) {
            throw new InvalidArgumentException('Items array cannot be empty.');
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
