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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser as ServiceUserSchema;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpTokens;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\WorkspaceHydratorInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class ServiceUserHydrator implements ServiceUserHydratorInterface
{
    public function __construct(
        private WorkspaceHydratorInterface $workspaceHydrator,
        private bool $clientCredentialsEnabled,
    ) {
    }

    public function hydrate(
        User $serviceUser,
        ?string $clientId,
        ServiceUserMcpTokens $mcpTokens
    ): ServiceUserSchema {
        return new ServiceUserSchema(
            id: (int) $serviceUser->getId(),
            name: (string) $serviceUser->getName(),
            email: $serviceUser->getEmail(),
            active: $serviceUser->isActive(),
            permissions: $serviceUser->getPermissions(),
            roles: $serviceUser->getRoles(),
            classes: $serviceUser->getClasses(),
            docTypes: $serviceUser->getDocTypes(),
            assetWorkspaces: $this->workspaceHydrator->hydrateAssetWorkspace($serviceUser),
            dataObjectWorkspaces: $this->workspaceHydrator->hydrateDataObjectWorkspace($serviceUser),
            documentWorkspaces: $this->workspaceHydrator->hydrateDocumentWorkspace($serviceUser),
            clientId: $clientId,
            lastLogin: $serviceUser->getLastLogin(),
            clientCredentialsEnabled: $this->clientCredentialsEnabled,
            mcpTokens: $mcpTokens,
            responsibleUsers: $serviceUser->getResponsibleUsers(),
            responsibleRoles: $serviceUser->getResponsibleRoles(),
        );
    }
}
