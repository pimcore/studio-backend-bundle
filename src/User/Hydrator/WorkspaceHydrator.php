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
