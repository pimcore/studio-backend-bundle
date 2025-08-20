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
final readonly class IdsParameter
{
    /**
     * @param array<int> $ids
     */
    public function __construct(
        private array $ids
    ) {
        if (empty($this->ids)) {
            throw new InvalidArgumentException('Ids array cannot be empty.');
        }
    }

    /**
     * @return array<int>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
