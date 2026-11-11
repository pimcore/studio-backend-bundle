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
 * @internal
 */
final readonly class UserAccountResolver implements UserAccountResolverInterface
{
    public function getAccountById(int $id): ?User
    {
        return User::getAccountById($id);
    }

    public function getServiceUserById(int $id): ?ServiceUser
    {
        return ServiceUser::getById($id);
    }
}
