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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Role\Event\RoleEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\UserRole;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class RoleService implements RoleServiceInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws DatabaseException
     */
    public function getRoles(): Collection
    {
        $roles = $this->roleRepository->getRoles();
        $items = [];

        foreach ($roles as $role) {
            $item = new UserRole(
                $role->getId(),
                $role->getName(),
            );

            $this->eventDispatcher->dispatch(
                new RoleEvent($item),
                RoleEvent::EVENT_NAME
            );

            $items[] = $item;
        }

        return new Collection(\count($items), $items);
    }
}
