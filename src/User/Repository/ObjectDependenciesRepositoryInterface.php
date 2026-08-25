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

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
interface ObjectDependenciesRepositoryInterface
{
    /**
     * Finds DataObjects referencing the given user via a `User`-type class field, without ever
     * hydrating more than $limit objects - regardless of how many objects reference the user in total.
     *
     * @return array{items: Concrete[], totalItems: int}
     */
    public function getObjectsReferencingUser(int $userId, int $offset, int $limit): array;
}
