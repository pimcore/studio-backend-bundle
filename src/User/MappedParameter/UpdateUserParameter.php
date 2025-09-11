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

namespace Pimcore\Bundle\StudioBackendBundle\User\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @internal
 */
final readonly class UpdateUserParameter
{
    public function __construct(
        private ?string $email,
        private ?string $firstname,
        private ?string $lastname,
        private bool $active,
        private bool $admin,
        private array $classes,
        private bool $closeWarning,
        private bool $allowDirtyClose,
        private array $contentLanguages,
        private array $keyBindings,
        #[NotBlank(message: 'Language is required')]
        private string $language,
        private bool $memorizeTabs,
        #[PositiveOrZero(message: 'ParentId must be a positive integer')]
        #[NotBlank(message: 'ParentId is required')]
        private int $parentId,
        private array $permissions,
        private array $roles,
        private bool $twoFactorAuthenticationRequired,
        private array $websiteTranslationLanguagesEdit,
        private array $websiteTranslationLanguagesView,
        private bool $welcomeScreen,
        private array $assetWorkspaces,
        private array $dataObjectWorkspaces,
        private array $documentWorkspaces,
        private array $perspectives = [],
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function isCloseWarning(): bool
    {
        return $this->closeWarning;
    }

    public function isAllowDirtyClose(): bool
    {
        return $this->allowDirtyClose;
    }

    public function getContentLanguages(): array
    {
        return $this->contentLanguages;
    }

    /**
     * @return KeyBinding[]
     */
    public function getKeyBindings(): array
    {
        return $this->keyBindings;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function isMemorizeTabs(): bool
    {
        return $this->memorizeTabs;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isTwoFactorAuthenticationRequired(): bool
    {
        return $this->twoFactorAuthenticationRequired;
    }

    public function getWebsiteTranslationLanguagesEdit(): array
    {
        return $this->websiteTranslationLanguagesEdit;
    }

    public function getWebsiteTranslationLanguagesView(): array
    {
        return $this->websiteTranslationLanguagesView;
    }

    public function isWelcomeScreen(): bool
    {
        return $this->welcomeScreen;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getAssetWorkspaces(): array
    {
        return $this->assetWorkspaces;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getDataObjectWorkspaces(): array
    {
        return $this->dataObjectWorkspaces;
    }

    /**
     * @return UserWorkspace[]
     */
    public function getDocumentWorkspaces(): array
    {
        return $this->documentWorkspaces;
    }

    /**
     * @return string[]
     */
    public function getPerspectives(): array
    {
        return $this->perspectives;
    }
}
