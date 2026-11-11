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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\EditLock;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\EditLockUser;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Pimcore\Model\Element\Editlock as EditLockModel;

/**
 * @internal
 */
final readonly class EditLockHydrator implements EditLockHydratorInterface
{
    public function __construct(
        private UserAccountResolverInterface $userAccountResolver,
    ) {
    }

    public function hydrateEditLock(?EditLockModel $editLock): EditLock
    {
        if ($editLock === null) {
            return new EditLock(false);
        }

        $user = $this->userAccountResolver->getAccountById($editLock->getUserId());
        $editLockUser = $user !== null ? new EditLockUser($user->getName()) : null;

        return new EditLock(
            true,
            $editLock->getUserId(),
            $editLock->getDate(),
            $editLockUser,
        );
    }
}
