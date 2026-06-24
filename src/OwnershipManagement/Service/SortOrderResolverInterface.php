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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service;

/**
 * @internal
 */
interface SortOrderResolverInterface
{
    /**
     * @param array<array{field?: string, direction?: string}> $sortBy
     * @param string[] $sortableFields
     *
     * @return list<array{field: string, direction: string}>
     */
    public function resolve(array $sortBy, array $sortableFields, string $defaultField): array;
}
