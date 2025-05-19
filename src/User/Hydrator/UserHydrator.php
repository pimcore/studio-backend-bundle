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

use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PermissionSanitationTrait;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class UserHydrator implements UserHydratorInterface
{
    use PermissionSanitationTrait;

    public function __construct(
        private ContentLanguagesHydratorInterface $contentLanguagesHydrator,
        private WorkspaceHydratorInterface $workspaceHydrator,
        private ObjectDependenciesServiceInterface $objectDependenciesService,
        private KeyBindingServiceInterface $keyBindingService,
        private UserPerspectiveServiceInterface $userPerspectiveService
    ) {
    }

    public function hydrate(UserInterface $user): UserSchema
    {
        return new UserSchema(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            firstname: $user->getFirstname(),
            lastname: $user->getLastname(),
            active: $user->getActive(),
            admin: $user->isAdmin(),
            classes: $user->getClasses(),
            closeWarning: $user->getCloseWarning(),
            allowDirtyClose: $user->getAllowDirtyClose(),
            contentLanguages: $this->contentLanguagesHydrator->hydrate($user),
            hasImage: $user->hasImage(),
            keyBindings: $this->keyBindingService->hydrateKeyBindings($user->getKeyBindings()),
            language: $user->getLanguage(),
            lastLogin: $user->getLastLogin(),
            memorizeTabs: $user->getMemorizeTabs(),
            parentId: $user->getParentId(),
            permissions: $this->sanitizePermissions($user->getPermissions()),
            roles: $user->getRoles(),
            twoFactorAuthenticationEnabled:
                $user->getTwoFactorAuthentication('enabled') || $user->getTwoFactorAuthentication('secret'),
            websiteTranslationLanguagesEdit: $user->getWebsiteTranslationLanguagesEdit(),
            websiteTranslationLanguagesView: $user->getWebsiteTranslationLanguagesView(),
            welcomeScreen: $user->getWelcomeScreen(),
            assetWorkspaces: $this->workspaceHydrator->hydrateAssetWorkspace($user),
            dataObjectWorkspaces: $this->workspaceHydrator->hydrateDataObjectWorkspace($user),
            documentWorkspaces: $this->workspaceHydrator->hydrateDocumentWorkspace($user),
            objectDependencies: $this->objectDependenciesService->getDependenciesForUser($user),
            perspectives: $this->userPerspectiveService->getConfigPerspectives($user),
        );
    }
}
