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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @internal
 */
final readonly class UserHydrator implements UserHydratorInterface
{
    public function __construct(
        private LoggerInterface $pimcoreLogger,
        private ToolResolverInterface $toolResolver,
        private AdminResolverInterface $adminToolResolver,
        private WorkspaceHydratorInterface $workspaceHydrator,
    ) {
    }

    public function hydrate(UserInterface $user): UserSchema
    {
        // TODO: Remove when https://github.com/pimcore/pimcore/issues/17196 is fixed.
        try {
            $lastLogin = $user->getLastLogin();
        } catch (Throwable) {
            $lastLogin = null;
        }

        return new UserSchema(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            firstname: $user->getFirstname(),
            lastname: $user->getLastname(),
            active: $user->getActive(),
            classes: $user->getClasses(),
            closeWarning: $user->getCloseWarning(),
            allowDirtyClose: $user->getAllowDirtyClose(),
            contentLanguages: $this->getContentLanguages($user),
            hasImage: $user->hasImage(),
            keyBindings: $this->hydrateKeyBindings($user->getKeyBindings()),
            language: $user->getLanguage(),
            lastLogin: $lastLogin,
            memorizeTabs: $user->getMemorizeTabs(),
            parentId: $user->getParentId(),
            permissions: $user->getPermissions(),
            roles: $user->getRoles(),
            twoFactorAuthenticationEnabled:
                $user->getTwoFactorAuthentication('enabled') || $user->getTwoFactorAuthentication('secret'),
            websiteTranslationLanguagesEdit: $user->getWebsiteTranslationLanguagesEdit(),
            websiteTranslationLanguagesView: $user->getWebsiteTranslationLanguagesView(),
            welcomeScreen: $user->getWelcomeScreen(),
            assetWorkspaces: $this->workspaceHydrator->hydrateAssetWorkspace($user),
            dataObjectWorkspaces: $this->workspaceHydrator->hydrateDataObjectWorkspace($user),
            documentWorkspaces: $this->workspaceHydrator->hydrateDocumentWorkspace($user),
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

            foreach ($decoded as $binding) {
                $bindings[] = new KeyBinding(
                    key: $binding['key'],
                    action: $binding['action'],
                    ctrl: $binding['ctrl'],
                    alt: $binding['alt'],
                    shift: $binding['shift'],
                );
            }

            return $bindings;
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
