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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\User\Workspace\AbstractWorkspace;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class WorkspaceHydrator implements WorkspaceHydratorInterface
{
    /**
     * @return UserWorkspace[]
     */
    public function hydrateAssetWorkspace(UserInterface|UserRoleInterface $user): array
    {
        $workspaces = [];
        foreach ($user->getWorkspacesAsset() as $workspace) {
            $workspaces[] = $this->hydrate($workspace);
        }

        return $workspaces;
    }

    /**
     * @return UserWorkspace[]
     */
    public function hydrateDataObjectWorkspace(UserInterface|UserRoleInterface $user): array
    {
        $workspaces = [];
        foreach ($user->getWorkspacesObject() as $workspace) {
            $workspaces[] = $this->hydrate($workspace);
        }

        return $workspaces;
    }

    /**
     * @return UserWorkspace[]
     */
    public function hydrateDocumentWorkspace(UserInterface|UserRoleInterface $user): array
    {
        $workspaces = [];
        foreach ($user->getWorkspacesDocument() as $workspace) {
            $workspaces[] = $this->hydrate($workspace);
        }

        return $workspaces;
    }

    private function hydrate(AbstractWorkspace $workspace): UserWorkspace
    {
        return new UserWorkspace(
            $workspace->getCid(),
            $workspace->getCpath(),
            $workspace->getList(),
            $workspace->getView(),
            $workspace->getPublish(),
            $workspace->getDelete(),
            $workspace->getRename(),
            $workspace->getCreate(),
            $workspace->getSettings(),
            $workspace->getVersions(),
            $workspace->getProperties(),
        );
    }
}
