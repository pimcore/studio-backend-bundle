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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'User',
    description: 'User Schema to update a User.',
    required: [
        'active', 'classes', 'closeWarning', 'allowDirtyClose', 'contentLanguages', 'keyBindings',
        'language', 'memorizeTabs', 'parentId', 'permissions', 'roles', 'twoFactorAuthenticationEnabled',
        'websiteTranslationLanguagesEdit', 'websiteTranslationLanguagesView', 'welcomeScreen',
        'assetWorkspaces', 'dataObjectWorkspaces', 'documentWorkspaces',
    ],
    type: 'object'
)]
final readonly class UpdateUser
{
    public function __construct(
        #[Property(description: 'Email of the User', type: 'string', example: '')]
        private ?string $email,
        #[Property(description: 'Firstname of the User', type: 'string', example: '')]
        private ?string $firstname,
        #[Property(description: 'Lastname of the User', type: 'string', example: '')]
        private ?string $lastname,
        #[Property(description: 'If User is admin', type: 'boolean', example: false)]
        private bool $admin,
        #[Property(description: 'If User is active', type: 'boolean', example: true)]
        private bool $active,
        #[Property(description: 'Classes the user is allows to see', type: 'object', example: ['CAR'])]
        private array $classes,
        #[Property(type: 'boolean', example: true)]
        private bool $closeWarning,
        #[Property(type: 'boolean', example: true)]
        private bool $allowDirtyClose,
        #[Property(
            description: 'List of available content Language already sorted.',
            type: 'object',
            example: ['de', 'en']
        )]
        private array $contentLanguages,
        #[Property(description: 'Key Bindings', type: 'array', items: new Items(ref: KeyBinding::class))]
        private array $keyBindings,
        #[Property(description: 'Language of the User', type: 'string', example: 'de')]
        private string $language,
        #[Property(type: 'boolean', example: true)]
        private bool $memorizeTabs,
        #[Property(type: 'int', example: '2')]
        private int $parentId,
        #[Property(description: 'List of permissions for the user', type: 'object', example: ['objects', 'documents'])]
        private array $permissions,
        #[Property(description: 'ID List of roles the user is assigned', type: 'object', example: [12, 14])]
        private array $roles,
        #[Property(type: 'boolean', example: false)]
        private bool $twoFactorAuthenticationEnabled,
        #[Property(type: 'object', example: ['de', 'en'])]
        private array $websiteTranslationLanguagesEdit,
        #[Property(type: 'object', example: ['de'])]
        private array $websiteTranslationLanguagesView,
        #[Property(type: 'boolean', example: true)]
        private bool $welcomeScreen,
        #[Property(description: 'Asset Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $assetWorkspaces,
        #[Property(description: 'Data Object Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $dataObjectWorkspaces,
        #[Property(description: 'Document Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $documentWorkspaces,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getLastLogin(): ?int
    {
        return $this->lastLogin;
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

    public function isAllowDirtyClose(): bool
    {
        return $this->allowDirtyClose;
    }

    public function isTwoFactorAuthenticationEnabled(): bool
    {
        return $this->twoFactorAuthenticationEnabled;
    }

    public function getWebsiteTranslationLanguagesEdit(): array
    {
        return $this->websiteTranslationLanguagesEdit;
    }

    public function getWebsiteTranslationLanguagesView(): array
    {
        return $this->websiteTranslationLanguagesView;
    }

    public function getWelcomeScreen(): bool
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
}
