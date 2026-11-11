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

namespace Pimcore\Bundle\StudioBackendBundle\User\Resolver;

use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;

/**
 * Lookups that accept a service user. The static resolver's getById() returns humans only, like
 * User::getById(); code that works for either kind (authentication, job owners, author names) uses this one.
 *
 * @internal
 */
interface UserAccountResolverInterface
{
    /**
     * The user or service user with this id; null for roles, folders and unknown ids.
     */
    public function getAccountById(int $id): ?User;

    /**
     * The service user with this id; null for a human user, a role, a folder or an unknown id.
     */
    public function getServiceUserById(int $id): ?ServiceUser;
}
