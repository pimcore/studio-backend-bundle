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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PermissionSanitationTrait;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final readonly class UserHydrator implements UserHydratorInterface
{
    use PermissionSanitationTrait;

    public function __construct(
        private LoggerInterface $pimcoreLogger,
        private ToolResolverInterface $toolResolver,
        private AdminResolverInterface $adminToolResolver,
        private WorkspaceHydratorInterface $workspaceHydrator,
        private ObjectDependenciesServiceInterface $objectDependenciesService,
        private KeyBindingHydratorInterface $keyBindingHydrator,
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
            contentLanguages: $this->getContentLanguages($user),
            hasImage: $user->hasImage(),
            keyBindings: $this->hydrateKeyBindings($user->getKeyBindings()),
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

    private function hydrateKeyBindings(?string $keyBindings): array
    {
        $bindings = [];

        if (!$keyBindings) {
            return $bindings;
        }

        try {
            $decoded = json_decode($keyBindings, true, 512, JSON_THROW_ON_ERROR);

            return $this->keyBindingHydrator->hydrate($decoded);
        } catch (Exception $e) {
            $this->pimcoreLogger->warning('Failed to decode key bindings', ['exception' => $e]);

            return [];
        }
    }

    private function getContentLanguages(UserInterface $user): array
    {
        $validLanguages = $this->toolResolver->getValidLanguages();
        /** @var User $user */
        $contentLanguagesString = $this->adminToolResolver->reorderWebsiteLanguages($user, $validLanguages);

        return explode(',', $contentLanguagesString);
    }
}
