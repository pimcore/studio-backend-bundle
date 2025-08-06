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

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class UserInformationHydrator implements UserInformationHydratorInterface
{
    public function __construct(
        private ContentLanguagesHydratorInterface $contentLanguagesHydrator,
        private KeyBindingServiceInterface $keyBindingService,
        private TwoFactorAuthHydratorInterface $twoFactorAuthHydrator,
        private UserPerspectiveServiceInterface $userPerspectiveService,
        private ToolResolverInterface $toolResolver
    ) {
    }

    public function hydrate(UserInterface $user): UserInformation
    {
        return new UserInformation(
            id: $user->getId(),
            username: $user->getUsername(),
            email: $user->getEmail(),
            firstname: $user->getFirstname(),
            lastname: $user->getLastname(),
            permissions: $user->getPermissions(),
            isAdmin: $user->isAdmin(),
            classes: $user->getClasses(),
            docTypes: $user->getDocTypes(),
            language: $user->getLanguage(),
            dateTimeLocale: $user instanceof User ? $user->getDateTimeLocale() : '',
            welcomeScreen: $user->getWelcomeScreen(),
            memorizeTabs: $user->getMemorizeTabs(),
            hasImage: $user->hasImage(),
            contentLanguages: $this->contentLanguagesHydrator->hydrate($user),
            allowedLanguagesForEditingWebsiteTranslations: $this->includeDisplayNameForLanguage(
                $user->getAllowedLanguagesForEditingWebsiteTranslations()
            ),
            allowedLanguagesForViewingWebsiteTranslations: $this->includeDisplayNameForLanguage(
                $user->getAllowedLanguagesForViewingWebsiteTranslations()
            ),
            keyBindings: $this->keyBindingService->hydrateKeyBindings($user->getKeyBindings()),
            twoFactorAuthentication: $this->twoFactorAuthHydrator->hydrate($user),
            activePerspective: $this->userPerspectiveService->getActivePerspective($user),
            perspectives: $this->userPerspectiveService->getAllowedPerspectives($user)
        );
    }

    public function includeDisplayNameForLanguage(array $languages): array
    {
        $systemLanguages = $this->toolResolver->getSupportedLocales();

        $languagesWithDisplayName = [];
        foreach ($languages as $language) {
            if (isset($systemLanguages[$language])) {
                $languagesWithDisplayName[] = [
                    'language' => $language,
                    'display' => $systemLanguages[$language],
                ];
            }
        }

        return $languagesWithDisplayName;
    }
}
