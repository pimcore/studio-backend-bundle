<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\User\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Model\User;
use Pimcore\Model\User\Listing as UserListing;

/**
 * @internal
 */
final class UserRepository implements UserRepositoryInterface
{
    public function getUserListingByParentId(int $parentId): UserListing
    {
        $listing = new UserListing();
        $listing->setCondition('parentId = ?', $parentId);
        $listing->setOrder('ASC');
        $listing->setOrderKey('name');
        $listing->load();

        return $listing;
    }

    /**
     * @throws NotFoundException
     */
    public function getUserById(int $userId): User
    {
        $user = User::getById($userId);

        if (!$user instanceof User) {
            throw new NotFoundException('User not found');
        }

        return $user;
    }

    /**
     * @throws Exception
     */
    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
