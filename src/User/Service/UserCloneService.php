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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserTreeNode;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class UserCloneService implements UserCloneServiceInterface
{
    public function __construct(
        private readonly WorkspaceCloneServiceInterface $workspaceCloneService,
        private readonly UserTreeNodeHydratorInterface $userTreeNodeHydrator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SecurityServiceInterface $securityService
    )
    {
    }

    private ?User $user;

    private ?User $userToClone;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function cloneUser(int $userId, string $userName): UserTreeNode
    {
        $this->userToClone = User::getById($userId);
        if (!$this->userToClone) {
            throw new NotFoundException(sprintf('User with id %s not found', $userId));
        }

        $this->createUser();
        $this->setUserProperties($userName);

        try {
            $this->user->save(); // save user to get id
            $this->cloneAndAssignWorkspaces();
            $this->user->save(); // save user with workspaces
        } catch (Exception $e) {
            throw new DatabaseException("Could not save user: " . $e->getMessage());
        }

        $treeNode = $this->userTreeNodeHydrator->hydrate($this->user);

        $this->eventDispatcher->dispatch(
            new UserTreeNodeEvent($treeNode),
            UserTreeNodeEvent::EVENT_NAME
        );

        return $treeNode;
    }

    private function createUser(): void
    {
        $this->user = new User();
    }

    private function setUserProperties(string $userName): void
    {
        $this->user->setParentId($this->userToClone->getParentId());
        $this->user->setName($userName);
        $this->user->setActive($this->userToClone->getActive());
        $this->user->setPerspectives($this->userToClone->getPerspectives());
        $this->user->setPermissions($this->userToClone->getPermissions());
        $this->user->setAdmin(false);
        if ($this->securityService->getCurrentUser()->isAdmin()) {
            $this->user->setAdmin($this->userToClone->isAdmin());
        }
        $this->user->setRoles($this->userToClone->getRoles());
        $this->user->setWelcomeScreen($this->userToClone->getWelcomescreen());
        $this->user->setMemorizeTabs($this->userToClone->getMemorizeTabs());
        $this->user->setCloseWarning($this->userToClone->getCloseWarning());
        $this->user->setWebsiteTranslationLanguagesView($this->userToClone->getWebsiteTranslationLanguagesView());
        $this->user->setWebsiteTranslationLanguagesEdit($this->userToClone->getWebsiteTranslationLanguagesEdit());

        if ($this->userToClone->getClasses()) {
            $this->user->setClasses(implode(',', $this->userToClone->getClasses()));
        }

        if ($this->userToClone->getDocTypes()) {
            $this->user->setDocTypes(implode(',', $this->userToClone->getDocTypes()));
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
        $assetWorkspace = $this->userToClone->getWorkspacesAsset();
        $workspaces = [];
        foreach ($assetWorkspace as $workspace) {
            $clonedWorkspace = $this->workspaceCloneService->cloneAssetWorkspace($workspace);
            $clonedWorkspace->setUserId($this->user->getId());
            $workspaces[] = $clonedWorkspace;
        }
        $this->user->setWorkspacesAsset($workspaces);
    }

    /**
     * @throws Exception
     */
    private function cloneAndAssignDocumentWorkspace(): void
    {
        $documentWorkspace = $this->userToClone->getWorkspacesDocument();
        $workspaces = [];
        foreach ($documentWorkspace as $workspace) {
            $clonedWorkspace = $this->workspaceCloneService->cloneDocumentWorkspace($workspace);
            $clonedWorkspace->setUserId($this->user->getId());
            $workspaces[] = $clonedWorkspace;
        }
        $this->user->setWorkspacesDocument($workspaces);
    }

    /**
     * @throws Exception
     */
    private function cloneAndAssignDataObjectWorkspace(): void
    {
        $dataObjectWorkspace = $this->userToClone->getWorkspacesObject();
        $workspaces = [];
        foreach ($dataObjectWorkspace as $workspace) {
            $clonedWorkspace = $this->workspaceCloneService->cloneDataObjectWorkspace($workspace);
            $clonedWorkspace->setUserId($this->user->getId());
            $workspaces[] = $clonedWorkspace;
        }
        $this->user->setWorkspacesObject($workspaces);
    }
}
