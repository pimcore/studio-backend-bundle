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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDataObjectWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDocumentWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;
use Pimcore\Model\UserInterface;
use function in_array;

/**
 * @internal
 */
final readonly class UpdateService implements UpdateServiceInterface
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
        private RoleRepositoryInterface $roleRepository,
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private ServiceResolverInterface $elementServiceResolver,
        private UserPerspectiveServiceInterface $userPerspectiveService,
    ) {
    }

    /**
     * @template T of UserInterface|UserRoleInterface
     *
     * @param T $user
     *
     * @throws NotFoundException
     *
     * @return T
     */
    public function updatePermissions(
        array $permissionsToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface {
        $permissions = array_map(static function ($permission) {
            return $permission->getKey();
        }, $this->permissionRepository->getAvailablePermissions());

        foreach ($permissionsToSet as $permission) {
            if (!in_array($permission, $permissions, true)) {
                throw new NotFoundException('Permission', $permission, 'Key');
            }
        }

        $user->setPermissions($permissionsToSet);

        return $user;
    }

    /**
     * @throws NotFoundException
     */
    public function updateRoles(array $rolesToSet, UserInterface $user): UserInterface
    {
        $roles = array_map(static function ($role) {
            return $role->getId();
        }, $this->roleRepository->getRoles());

        foreach ($rolesToSet as $role) {
            if (!in_array($role, $roles, true)) {
                throw new NotFoundException('Role', $role);
            }
        }

        $user->setRoles($rolesToSet);

        return $user;
    }

    /**
     * @template T of UserInterface|UserRoleInterface
     *
     * @param T $user
     *
     * @throws NotFoundException
     *
     * @return T
     */
    public function updateClasses(
        array $classesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface {
        $classes = array_map(static function ($class) {
            return $class->getId();
        }, $this->classDefinitionRepository->getClassDefinitions());

        foreach ($classesToSet as $class) {
            if (!in_array($class, $classes, true)) {
                throw new NotFoundException('Class', $class);
            }
        }

        $user->setClasses($classesToSet);

        return $user;
    }

    /**
     * @template T of UserInterface|UserRoleInterface
     *
     * @param UserWorkspace[] $assetWorkspacesToSet
     * @param T $user
     *
     * @throws ParseException
     *
     * @return T
     */
    public function updateAssetWorkspaces(
        array $assetWorkspacesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface {
        $this->checkForDuplicateWorkspaces($assetWorkspacesToSet);

        $workspaces = [];
        foreach ($assetWorkspacesToSet as $workspace) {
            $element = $this->elementServiceResolver->getElementByPath('asset', $workspace->getCpath());
            if ($element) {
                $workspaces[] = $this->setWorkspaceValues($workspace, $element, new AssetWorkspace(), $user->getId());
            }
        }

        /** @var AssetWorkspace[] $workspaces */
        $user->setWorkspacesAsset($workspaces);

        return $user;
    }

    /**
     * @template T of UserInterface|UserRoleInterface
     *
     * @param UserDataObjectWorkspace[] $objectWorkspacesToSet
     * @param T $user
     *
     * @throws ParseException
     *
     * @return T
     */
    public function updateDataObjectWorkspaces(
        array $objectWorkspacesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface {
        $this->checkForDuplicateWorkspaces($objectWorkspacesToSet);

        $workspaces = [];
        foreach ($objectWorkspacesToSet as $workspace) {
            $element = $this->elementServiceResolver->getElementByPath('object', $workspace->getCpath());
            if ($element) {
                $hydrated = $this->setWorkspaceValues(
                    $workspace,
                    $element,
                    new DataObjectWorkspace(),
                    $user->getId()
                );

                $workspaces[] = $this->setDataObjectWorkspaceValues($workspace, $hydrated);
            }
        }

        /** @var DataObjectWorkspace[] $workspaces */
        $user->setWorkspacesObject($workspaces);

        return $user;
    }

    /**
     * @template T of UserInterface|UserRoleInterface
     *
     * @param UserDocumentWorkspace[] $documentWorkspacesToSet
     * @param T $user
     *
     * @throws ParseException
     *
     * @return T
     */
    public function updateDocumentWorkspaces(
        array $documentWorkspacesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface {
        $this->checkForDuplicateWorkspaces($documentWorkspacesToSet);

        $workspaces = [];
        foreach ($documentWorkspacesToSet as $workspace) {
            $element = $this->elementServiceResolver->getElementByPath('document', $workspace->getCpath());
            if ($element) {
                $hydrated = $this->setWorkspaceValues(
                    $workspace,
                    $element,
                    new DocumentWorkspace(),
                    $user->getId()
                );

                $workspaces[] = $this->setDocumentWorkspaceValues($workspace, $hydrated);
            }
        }

        /** @var DocumentWorkspace[] $workspaces */
        $user->setWorkspacesDocument($workspaces);

        return $user;
    }

    /**
     * @template T of UserInterface|UserRoleInterface
     *
     * @param T $user
     *
     * @return T
     */
    public function updatePerspectives(
        array $perspectivesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface {
        $this->userPerspectiveService->updatePerspectives($perspectivesToSet, $user);

        return $user;
    }

    /**
     * @param UserWorkspace[] $assetWorkspacesToSet
     *
     * @throws ParseException
     */
    private function checkForDuplicateWorkspaces(array $assetWorkspacesToSet): void
    {
        $paths = array_map(static function ($workspace) {
            return $workspace->getCpath();
        }, $assetWorkspacesToSet);

        if (array_unique($paths) !== $paths) {
            throw new ParseException(
                "Duplicate workspaces are not allowed. User can't have the same workspace multiple times."
            );
        }
    }

    private function setWorkspaceValues(
        UserWorkspace $params,
        ElementInterface $element,
        AssetWorkspace|DataObjectWorkspace|DocumentWorkspace $workspace,
        int $userId
    ): AssetWorkspace|DataObjectWorkspace|DocumentWorkspace {
        $workspace->setUserId($userId);
        $workspace->setCpath($element->getRealFullPath());
        $workspace->setCid($element->getId());
        $workspace->setList($params->hasList());
        $workspace->setView($params->hasView());
        $workspace->setPublish($params->hasPublish());
        $workspace->setDelete($params->hasDelete());
        $workspace->setRename($params->hasRename());
        $workspace->setCreate($params->hasCreate());
        $workspace->setSettings($params->hasSettings());
        $workspace->setVersions($params->hasVersions());
        $workspace->setProperties($params->hasProperties());

        return $workspace;
    }

    private function setDataObjectWorkspaceValues(
        UserDataObjectWorkspace $params,
        DataObjectWorkspace $workspace
    ): DataObjectWorkspace {
        $hydrated = $this->setSpecialWorkspaceValues($params, $workspace);
        $hydrated->setLEdit($this->transformLocalizedValues($params->getLocalizedEdit()));
        $hydrated->setLView($this->transformLocalizedValues($params->getLocalizedView()));
        $hydrated->setLayouts($this->transformLocalizedValues($params->getLayouts()));

        return $hydrated;
    }

    private function setDocumentWorkspaceValues(
        UserDocumentWorkspace $params,
        DocumentWorkspace $workspace
    ): DocumentWorkspace {

        return $this->setSpecialWorkspaceValues($params, $workspace);
    }

    private function setSpecialWorkspaceValues(
        UserDocumentWorkspace|UserDataObjectWorkspace $params,
        DataObjectWorkspace|DocumentWorkspace $workspace
    ): DataObjectWorkspace|DocumentWorkspace {
        $workspace->setUnpublish($params->isUnpublish());
        $workspace->setSave($params->isSave());

        return $workspace;
    }

    private function transformLocalizedValues(?array $workspaceValue): ?string
    {
        return empty($workspaceValue) ? null : implode(',', $workspaceValue);
    }
}
