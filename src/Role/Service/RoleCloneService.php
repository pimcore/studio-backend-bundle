<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Role\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Role\Event\RoleTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Hydrator\RoleTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneServiceInterface;
use Pimcore\Model\User\Role;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class RoleCloneService implements RoleCloneServiceInterface
{

    private ?Role $roleToClone;

    private ?Role $role;

    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WorkspaceCloneServiceInterface $workspaceCloneService,
        private readonly RoleTreeNodeHydratorInterface $roleTreeNodeHydrator
    )
    {
    }


    /**
     * @throws DatabaseException|NotFoundException
     */
    public function cloneRole(int $roleId, string $roleName): TreeNode
    {
        $this->roleToClone = $this->roleRepository->getRoleById($roleId);

        $this->createRole();
        $this->setRoleProperties($roleName);

        try {
            $this->role->save(); // save role to get id
            $this->cloneAndAssignWorkspaces();
            $this->role->save(); // save role with workspaces
        } catch (Exception $e) {
            throw new DatabaseException('Could not save Role: ' . $e->getMessage());
        }

        $treeNode = $this->roleTreeNodeHydrator->hydrate($this->role);

        $this->eventDispatcher->dispatch(
            new RoleTreeNodeEvent($treeNode),
            RoleTreeNodeEvent::EVENT_NAME
        );

        return $treeNode;
    }

    private function createRole(): void
    {
        $this->role = new Role();
    }

    private function setRoleProperties(string $roleName): void
    {
        $this->role->setName($roleName);
        $this->role->setParentId($this->roleToClone->getParentId());
        $this->role->setPerspectives($this->roleToClone->getPerspectives());
        $this->role->setPermissions($this->roleToClone->getPermissions());
        $this->role->setWebsiteTranslationLanguagesEdit($this->roleToClone->getWebsiteTranslationLanguagesEdit());
        $this->role->setWebsiteTranslationLanguagesView($this->roleToClone->getWebsiteTranslationLanguagesView());


        if ($this->roleToClone->getClasses()) {
            $this->role->setClasses(implode(',', $this->roleToClone->getClasses()));
        }

        if ($this->roleToClone->getDocTypes()) {
            $this->role->setDocTypes(implode(',', $this->roleToClone->getDocTypes()));
        }
    }

    /**
     * @throws Exception
     */
    private function cloneAndAssignWorkspaces(): void
    {
        $this->cloneAndAssignAssetWorkspace();
        $this->cloneAndAssignDocumentWorkspace();
        $this->cloneAndAssignDataObjectWorkspace();
    }

    /**
     * @throws Exception
     */
    private function cloneAndAssignAssetWorkspace(): void
    {
        $assetWorkspace = $this->roleToClone->getWorkspacesAsset();
        $workspaces = [];
        foreach ($assetWorkspace as $workspace) {
            $clonedWorkspace = $this->workspaceCloneService->cloneAssetWorkspace($workspace);
            $clonedWorkspace->setUserId($this->role->getId());
            $workspaces[] = $clonedWorkspace;
        }
        $this->role->setWorkspacesAsset($workspaces);
    }

    /**
     * @throws Exception
     */
    private function cloneAndAssignDocumentWorkspace(): void
    {
        $documentWorkspace = $this->roleToClone->getWorkspacesDocument();
        $workspaces = [];
        foreach ($documentWorkspace as $workspace) {
            $clonedWorkspace = $this->workspaceCloneService->cloneDocumentWorkspace($workspace);
            $clonedWorkspace->setUserId($this->role->getId());
            $workspaces[] = $clonedWorkspace;
        }
        $this->role->setWorkspacesDocument($workspaces);
    }

    /**
     * @throws Exception
     */
    private function cloneAndAssignDataObjectWorkspace(): void
    {
        $dataObjectWorkspace = $this->roleToClone->getWorkspacesObject();
        $workspaces = [];
        foreach ($dataObjectWorkspace as $workspace) {
            $clonedWorkspace = $this->workspaceCloneService->cloneDataObjectWorkspace($workspace);
            $clonedWorkspace->setUserId($this->role->getId());
            $workspaces[] = $clonedWorkspace;
        }
        $this->role->setWorkspacesObject($workspaces);
    }
}