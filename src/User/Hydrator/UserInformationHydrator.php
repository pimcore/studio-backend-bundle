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

use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Model\UserInterface;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

/**
 * @internal
 */
final readonly class UserInformationHydrator implements UserInformationHydratorInterface
{
    public function __construct(
        private ContentLanguagesHydratorInterface $contentLanguagesHydrator,
        private KeyBindingServiceInterface $keyBindingService,
        private RoleResolverInterface $roleResolver,
        private TwoFactorAuthHydratorInterface $twoFactorAuthHydrator,
        private UserPermissionServiceInterface $userPermissionService,
        private UserPerspectiveServiceInterface $userPerspectiveService,
    ) {
    }

    public function hydrate(UserInterface $user): UserInformation
    {
        $roles = $this->getRoles($user);

        return new UserInformation(
            id: $user->getId(),
            username: $user->getUsername(),
            email: $user->getEmail(),
            firstname: $user->getFirstname(),
            lastname: $user->getLastname(),
            permissions: $this->userPermissionService->getAllowedUserPermissions($user),
            isAdmin: $user->isAdmin(),
            classes: $this->mergeWithRoles(
                $user->getClasses(),
                array_map(static fn (Role $role) => $role->getClasses(), $roles)
            ),
            docTypes: $this->mergeWithRoles(
                $user->getDocTypes(),
                array_map(static fn (Role $role) => $role->getDocTypes(), $roles)
            ),
            language: $user->getLanguage(),
            dateTimeLocale: $user instanceof User ? $user->getDateTimeLocale() : '',
            theme: $user instanceof User ? $user->getTheme() : 'default',
            welcomeScreen: $user->getWelcomeScreen(),
            memorizeTabs: $user->getMemorizeTabs(),
            allowDirtyClose: $user->getAllowDirtyClose(),
            hasImage: $user->hasImage(),
            contentLanguages: $this->contentLanguagesHydrator->hydrate($user),
            allowedLanguagesForEditingWebsiteTranslations:
                $user->getAllowedLanguagesForEditingWebsiteTranslations() ?? [],
            allowedLanguagesForViewingWebsiteTranslations:
                $user->getAllowedLanguagesForViewingWebsiteTranslations() ?? [],
            keyBindings: $this->keyBindingService->hydrateKeyBindings($user->getKeyBindings()),
            twoFactorAuthentication: $this->twoFactorAuthHydrator->hydrate($user),
            activePerspective: $this->userPerspectiveService->getActivePerspective($user),
            perspectives: $this->userPerspectiveService->getAllowedPerspectives($user),
            welcomeScreenTarget: $user instanceof User ? $user->getWelcomeScreenTarget() : null,
        );
    }

    /**
     * @return Role[]
     */
    private function getRoles(UserInterface $user): array
    {
        $roles = [];
        foreach ($user->getRoles() as $roleId) {
            $role = $this->roleResolver->getById((int)$roleId);
            if ($role instanceof Role) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * Mirrors Pimcore Core's User::isAllowed() for the 'class' and 'docType' types:
     * the grants assigned to the user and to all of their roles are combined.
     *
     * @param string[] $userGrants
     * @param array<int, string[]> $roleGrants
     *
     * @return string[]
     */
    private function mergeWithRoles(array $userGrants, array $roleGrants): array
    {
        return array_values(array_unique(array_merge($userGrants, ...$roleGrants)));
    }
}
