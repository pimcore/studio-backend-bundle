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

use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User\Workspace\AbstractWorkspace;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserUpdateService implements UserUpdateServiceInterface
{
    private UserInterface $user;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SecurityServiceInterface $securityService,
        private readonly UserHydratorInterface $userHydrator,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private readonly ServiceResolverInterface $elementServiceResolver,
        private readonly AuthenticationResolverInterface $authenticationResolver
    ) {
    }

    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException|ParseException
     */
    public function updateUserById(UpdateUserParameter $updateUserParameter, int $userId): UserSchema
    {
        $this->user = $this->userRepository->getUserById($userId);

        if ($this->user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin can update admin user');
        }

        if ($this->securityService->getCurrentUser()->isAdmin()) {
            $this->user->setAdmin($updateUserParameter->isAdmin());
        }

        $this->user->setEmail($updateUserParameter->getEmail());
        $this->user->setFirstName($updateUserParameter->getFirstName());
        $this->user->setLastName($updateUserParameter->getLastName());
        $this->user->setActive($updateUserParameter->isActive());
        $this->user->setCloseWarning($updateUserParameter->isCloseWarning());
        $this->user->setLanguage($updateUserParameter->getLanguage());
        $this->user->setMemorizeTabs($updateUserParameter->isMemorizeTabs());
        $this->user->setParentId($updateUserParameter->getParentId());
        $this->user->setAllowDirtyClose($updateUserParameter->isAllowDirtyClose());
        $this->user->setTwoFactorAuthentication('required', $updateUserParameter->isTwoFactorAuthenticationEnabled());
        $this->user->setWelcomescreen($updateUserParameter->isWelcomescreen());
        $this->user->setContentLanguages($updateUserParameter->getContentLanguages());
        $this->user->setWebsiteTranslationLanguagesEdit($updateUserParameter->getWebsiteTranslationLanguagesEdit());
        $this->user->setWebsiteTranslationLanguagesView($updateUserParameter->getWebsiteTranslationLanguagesView());
        $this->user->setKeyBindings(
            $this->getKeyBindingsString($updateUserParameter->getKeyBindings())
        );
        $this->updatePermissions($updateUserParameter->getPermissions());
        $this->updateRoles($updateUserParameter->getRoles());
        $this->updateClasses($updateUserParameter->getClasses());
        $this->updateAssetWorkspaces($updateUserParameter->getAssetWorkspaces());
        $this->updateDataObjectWorkspaces($updateUserParameter->getDataObjectWorkspaces());
        $this->updateDocumentWorkspaces($updateUserParameter->getDocumentWorkspaces());

        $this->userRepository->updateUser($this->user);

        return $this->userHydrator->hydrate($this->user);
    }


    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException
     */
    public function updatePasswordById(UpdatePasswordParameter $updateParameter, int $userId): void
    {
        $this->user = $this->userRepository->getUserById($userId);

        if ($this->user->getName() === 'system') {
            throw new ForbiddenException('System user password cannot be changed');
        }

        if ($this->user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin can update admin user');
        }

        if ($updateParameter->getPassword() !== $updateParameter->getPasswordConfirmation()) {
            throw new InvalidArgumentException('Passwords do not match');
        }

        if (strlen($updateParameter->getPassword()) < 10) {
            throw new InvalidArgumentException('Passwords have to be at least 10 characters long');
        }

        $passwordHash = $this->authenticationResolver->getPasswordHash(
            $this->user->getName(),
            $updateParameter->getPassword()
        );

        $this->user->setPassword($passwordHash);
        $this->userRepository->updateUser($this->user);
    }

    /**
     * @param KeyBinding[] $keyBindings
     *
     * @throws ParseException
     */
    private function getKeyBindingsString(array $keyBindings): string
    {
        $keyBindingString = [];
        foreach ($keyBindings as $keyBinding) {
            $bindings['key'] = $keyBinding->getKey();
            $bindings['action'] = $keyBinding->getAction();
            $bindings['ctrl'] = $keyBinding->getCtrl();
            $bindings['alt'] = $keyBinding->getAlt();
            $bindings['shift'] = $keyBinding->getShift();

            $keyBindingString[] = $bindings;
        }

        try {
            return json_encode($keyBindingString, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ParseException(sprintf('Error parsing key bindings: %s', $e->getMessage()));
        }
    }

    /**
     * @throws NotFoundException
     */
    private function updatePermissions(array $permissionsToSet): void
    {
        $permissions = array_map(static function ($permission) {
            return $permission->getKey();
        }, $this->permissionRepository->getAvailablePermissions());

        foreach ($permissionsToSet as $permission) {
            if (!in_array($permission, $permissions, true)) {
                throw new NotFoundException('Permission', $permission, 'Key');
            }
        }

        $this->user->setPermissions($permissionsToSet);
    }

    /**
     * @throws NotFoundException
     */
    private function updateRoles(array $rolesToSet): void
    {
        $roles = array_map(static function ($role) {
            return $role->getId();
        }, $this->roleRepository->getRoles());

        foreach ($rolesToSet as $role) {
            if (!in_array($role, $roles, true)) {
                throw new NotFoundException('Role', $role);
            }
        }

        $this->user->setRoles($rolesToSet);
    }

    /**
     * @throws NotFoundException
     */
    private function updateClasses(array $classesToSet): void
    {
        $classes = array_map(static function ($class) {
            return $class->getId();
        }, $this->classDefinitionRepository->getClassDefinitions());

        foreach ($classesToSet as $class) {
            if (!in_array($class, $classes, true)) {
                throw new NotFoundException('Class', $class);
            }
        }

        $this->user->setClasses($classesToSet);
    }

    /**
     * @param UserWorkspace[] $assetWorkspacesToSet
     *
     * @throws ParseException
     */
    private function updateAssetWorkspaces(array $assetWorkspacesToSet): void
    {
        $this->checkForDuplicateWorkspaces($assetWorkspacesToSet);

        $workspaces = [];
        foreach ($assetWorkspacesToSet as $workspace) {
            $element = $this->elementServiceResolver->getElementByPath('asset', $workspace->getCpath());
            if ($element) {
                $workspaces[] = $this->setWorkspaceValues($workspace, $element, new AssetWorkspace());
            }
        }

        /** @var AssetWorkspace[] $workspaces */
        $this->user->setWorkspacesAsset($workspaces);
    }

    /**
     * @param UserWorkspace[] $objectWorkspacesToSet
     *
     * @throws ParseException
     */
    private function updateDataObjectWorkspaces(array $objectWorkspacesToSet): void
    {
        $this->checkForDuplicateWorkspaces($objectWorkspacesToSet);

        $workspaces = [];
        foreach ($objectWorkspacesToSet as $workspace) {
            $element = $this->elementServiceResolver->getElementByPath('object', $workspace->getCpath());
            if ($element) {
                $workspaces[] = $this->setWorkspaceValues($workspace, $element, new DataObjectWorkspace());
            }
        }

        /** @var DataObjectWorkspace[] $workspaces */
        $this->user->setWorkspacesObject($workspaces);
    }

    /**
     * @param UserWorkspace[] $documentWorkspacesToSet
     *
     * @throws ParseException
     */
    private function updateDocumentWorkspaces(array $documentWorkspacesToSet): void
    {
        $this->checkForDuplicateWorkspaces($documentWorkspacesToSet);

        $workspaces = [];
        foreach ($documentWorkspacesToSet as $workspace) {
            $element = $this->elementServiceResolver->getElementByPath('document', $workspace->getCpath());
            if ($element) {
                $workspaces[] = $this->setWorkspaceValues($workspace, $element, new DocumentWorkspace());
            }
        }

        /** @var DocumentWorkspace[] $workspaces */
        $this->user->setWorkspacesDocument($workspaces);
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
        AbstractWorkspace $workspace
    ): AbstractWorkspace {
        $workspace->setUserId($this->user->getId());
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
}
