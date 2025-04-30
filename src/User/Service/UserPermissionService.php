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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserPermissionEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\PermissionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

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

        return new Collection(count($items), $items);
    }
}
