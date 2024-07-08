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
use Pimcore\Bundle\StudioBackendBundle\Role\Event\DetailedRoleEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Event\RoleTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Event\SimpleRoleEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Hydrator\RoleHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Hydrator\RoleTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\MappedParameter\UpdateRoleParameter;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\FolderRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\DetailedRole;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\SimpleRole;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateServiceInterface;
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
        private RoleHydratorInterface $roleHydrator,
        private FolderRepositoryInterface $folderRepository,
        private UpdateServiceInterface $updateService,
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
            $item = new SimpleRole(
                $role->getId(),
                $role->getName(),
            );

            $this->eventDispatcher->dispatch(
                new SimpleRoleEvent($item),
                SimpleRoleEvent::EVENT_NAME
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

        if ($createParameter->getParentId() !== 0) {
            $folderId = $this->folderRepository->getFolderById($createParameter->getParentId())->getId();
        }

        try {
            $role = $this->roleRepository->createRole($createParameter->getName(), $folderId);

            $role =  $this->roleTreeNodeHydrator->hydrate($role);

            $this->eventDispatcher->dispatch(
                new RoleTreeNodeEvent($role),
                RoleTreeNodeEvent::EVENT_NAME
            );

            return $role;
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error creating role: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @throws NotFoundException
     */
    public function getRoleById(int $roleId): DetailedRole
    {
        $role = $this->roleRepository->getRoleById($roleId);

        $role = $this->roleHydrator->hydrate($role);

        $this->eventDispatcher->dispatch(
            new DetailedRoleEvent($role),
            DetailedRoleEvent::EVENT_NAME
        );

        return $role;
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function updateRoleById(int $roleId, UpdateRoleParameter $updateRoleParameter): DetailedRole
    {
        $role = $this->roleRepository->getRoleById($roleId);

        $role->setParentId($updateRoleParameter->getParentId());
        $role->setWebsiteTranslationLanguagesEdit($updateRoleParameter->getWebsiteTranslationLanguagesEdit());
        $role->setWebsiteTranslationLanguagesView($updateRoleParameter->getWebsiteTranslationLanguagesView());
        $role->setDocTypes($updateRoleParameter->getDocTypes());

        $role = $this->updateService->updateClasses($updateRoleParameter->getClasses(), $role);
        $role = $this->updateService->updatePermissions($updateRoleParameter->getPermissions(), $role);
        $role = $this->updateService->updateAssetWorkspaces($updateRoleParameter->getAssetWorkspaces(), $role);
        $role = $this->updateService->updateDataObjectWorkspaces(
            $updateRoleParameter->getDataObjectWorkspaces(),
            $role
        );
        $role = $this->updateService->updateDocumentWorkspaces($updateRoleParameter->getDocumentWorkspaces(), $role);

        $this->roleRepository->updateRole($role);

        $role = $this->roleHydrator->hydrate($role);

        $this->eventDispatcher->dispatch(
            new DetailedRoleEvent($role),
            DetailedRoleEvent::EVENT_NAME
        );

        return $role;
    }
}
