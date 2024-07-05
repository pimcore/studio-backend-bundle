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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Role\Schema\DetailedRole;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\WorkspaceHydratorInterface;
use Pimcore\Model\User\UserRoleInterface;

/**
 * @internal
 */
final readonly class RoleHydrator implements RoleHydratorInterface
{
    public function __construct(
        private WorkspaceHydratorInterface $workspaceHydrator
    ) {
    }

    public function hydrate(UserRoleInterface $role): DetailedRole
    {
        return new DetailedRole(
            id: $role->getId(),
            name: $role->getName(),
            classes: $role->getClasses(),
            parentId: $role->getParentId(),
            permissions: $role->getPermissions(),
            docTypes: $role->getDocTypes(),
            websiteTranslationLanguagesEdit: $role->getWebsiteTranslationLanguagesEdit(),
            websiteTranslationLanguagesView: $role->getWebsiteTranslationLanguagesView(),
            assetWorkspaces: $this->workspaceHydrator->hydrateAssetWorkspace($role),
            dataObjectWorkspaces: $this->workspaceHydrator->hydrateDataObjectWorkspace($role),
            documentWorkspaces: $this->workspaceHydrator->hydrateDocumentWorkspace($role),
        );
    }
}
