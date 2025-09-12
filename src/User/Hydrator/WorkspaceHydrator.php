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

use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDataObjectWorkspace;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserDocumentWorkspace;
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
            $workspaces[] = new UserWorkspace(...$this->hydrateBaseWorkspace($workspace));
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
            $workspaces[] = new UserDataObjectWorkspace(
                $workspace->getSave(),
                $workspace->getUnpublish(),
                $workspace->getLEdit(),
                $workspace->getLView(),
                $workspace->getLayouts(),
                ...$this->hydrateBaseWorkspace($workspace)
            );
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
            $workspaces[] = new UserDocumentWorkspace(
                $workspace->getSave(),
                $workspace->getUnpublish(),
                ...$this->hydrateBaseWorkspace($workspace)
            );
        }

        return $workspaces;
    }

    private function hydrateBaseWorkspace(AbstractWorkspace $workspace): array
    {
        return [
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
        ];
    }
}
