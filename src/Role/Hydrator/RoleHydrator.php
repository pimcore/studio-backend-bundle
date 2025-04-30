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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Role\Schema\DetailedRole;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\WorkspaceHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PermissionSanitationTrait;
use Pimcore\Model\User\UserRoleInterface;

/**
 * @internal
 */
final readonly class RoleHydrator implements RoleHydratorInterface
{
    use PermissionSanitationTrait;

    public function __construct(
        private WorkspaceHydratorInterface $workspaceHydrator,
        private UserPerspectiveServiceInterface $userPerspectiveService
    ) {
    }

    public function hydrate(UserRoleInterface $role): DetailedRole
    {
        return new DetailedRole(
            id: $role->getId(),
            name: $role->getName(),
            classes: $role->getClasses(),
            parentId: $role->getParentId(),
            permissions: $this->sanitizePermissions($role->getPermissions()),
            docTypes: $role->getDocTypes(),
            websiteTranslationLanguagesEdit: $role->getWebsiteTranslationLanguagesEdit(),
            websiteTranslationLanguagesView: $role->getWebsiteTranslationLanguagesView(),
            assetWorkspaces: $this->workspaceHydrator->hydrateAssetWorkspace($role),
            dataObjectWorkspaces: $this->workspaceHydrator->hydrateDataObjectWorkspace($role),
            documentWorkspaces: $this->workspaceHydrator->hydrateDocumentWorkspace($role),
            perspectives: $this->userPerspectiveService->getConfigPerspectives($role),
        );
    }
}
