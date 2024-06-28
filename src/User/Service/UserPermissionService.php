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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserPermissionEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\PermissionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class UserPermissionService implements UserPermissionServiceInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionsRepository,
        private readonly PermissionHydratorInterface $permissionHydrator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getAvailablePermissions(): Collection
    {
        $permissions = $this->permissionsRepository->getAvailablePermissions();
        $items = [];
        foreach ($permissions as $permission) {
            $item = $this->permissionHydrator->hydrate($permission);

            $this->eventDispatcher->dispatch(
                new UserPermissionEvent($item),
                UserPermissionEvent::EVENT_NAME
            );

            $items[] = $item;
        }

        return new Collection(\count($items), $items);
    }
}
