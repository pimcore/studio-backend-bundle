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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Role\Event\RoleEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Event\RoleTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Hydrator\RoleTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\FolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\UserRole;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class RoleService implements RoleServiceInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private EventDispatcherInterface $eventDispatcher,
        private RoleTreeNodeHydratorInterface $roleTreeNodeHydrator,
        private FolderRepositoryInterface $folderRepository
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

        return new Collection(count($items), $items);
    }

    /**
     * @throws DatabaseException
     */
    public function getRoleTreeCollection(ParentIdParameter $listingParameter): Collection
    {
        $roles = $this->roleRepository->getRoleListingWithFolderByParentId($listingParameter->getParentId());

        $items = [];
        foreach ($roles->getRoles() as $role) {
            $item = $this->roleTreeNodeHydrator->hydrate($role);

            $this->eventDispatcher->dispatch(
                new RoleTreeNodeEvent($item),
                RoleTreeNodeEvent::EVENT_NAME
            );

            $items[] = $item;
        }

        return new Collection(count($items), $items);

    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function deleteRole(int $roleId): void
    {
        $role = $this->roleRepository->getRoleById($roleId);

        try {
            $this->roleRepository->deleteRole($role);
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf('Failed to delete role with id %d: %s', $roleId, $exception->getMessage()),
            );
        }
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function createRole(CreateParameter $createParameter): TreeNode
    {
        $folderId = 0;

        if($createParameter->getParentId() !== 0) {
            $folderId = $this->folderRepository->getFolderById($createParameter->getParentId())->getId();
        }

        try {
            $role = $this->roleRepository->createRole($createParameter->getName(), $folderId);

            return $this->roleTreeNodeHydrator->hydrate($role);
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error creating role: %s',
                    $exception->getMessage()
                )
            );
        }
    }
}
