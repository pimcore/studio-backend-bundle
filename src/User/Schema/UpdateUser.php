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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;

/**
 * @internal
 */
#[Schema(
    title: 'User',
    description: 'User Schema to update a User.',
    required: [
        'email', 'firstname', 'lastname', 'admin',
        'active', 'classes', 'docTypes', 'closeWarning', 'allowDirtyClose', 'contentLanguages', 'keyBindings',
        'language', 'memorizeTabs', 'parentId', 'permissions', 'roles', 'twoFactorAuthenticationRequired',
        'websiteTranslationLanguagesEdit', 'websiteTranslationLanguagesView', 'welcomeScreen',
        'assetWorkspaces', 'dataObjectWorkspaces', 'documentWorkspaces', 'perspectives',
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
        #[Property(description: 'Allowed Document types to create', type: 'object', example: ['3', '5'])]
        private array $docTypes,
        #[Property(description: 'Show Close Warning', type: 'boolean', example: true)]
        private bool $closeWarning,
        #[Property(description: 'Allow Dirty Close', type: 'boolean', example: true)]
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
        #[Property(description: 'Date Time Locale for the User', type: 'string', example: '')]
        private string $dateTimeLocale,
        #[Property(description: 'Memorize Tabs', type: 'boolean', example: true)]
        private bool $memorizeTabs,
        #[Property(description: 'Parent ID', type: 'integer', example: 2)]
        private int $parentId,
        #[Property(description: 'List of permissions for the user', type: 'object', example: ['objects', 'documents'])]
        private array $permissions,
        #[Property(description: 'ID List of roles the user is assigned', type: 'object', example: [12, 14])]
        private array $roles,
        #[Property(description: 'Two Factor Authentication Enabled', type: 'boolean', example: false)]
        private bool $twoFactorAuthenticationRequired,
        #[Property(description: 'Website Translation Languages Edit', type: 'object', example: ['de', 'en'])]
        private array $websiteTranslationLanguagesEdit,
        #[Property(description: 'Website Translation Languages View', type: 'object', example: ['de'])]
        private array $websiteTranslationLanguagesView,
        #[Property(description: 'Show welcome Screen', type: 'boolean', example: true)]
        private bool $welcomeScreen,
        #[Property(description: 'Asset Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $assetWorkspaces,
        #[Property(description: 'Data Object Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $dataObjectWorkspaces,
        #[Property(description: 'Document Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private array $documentWorkspaces,
        #[Property(
            description: 'Allowed studio perspectives',
            type: 'object',
            example: [Perspectives::DEFAULT_ID->value, 'some_otherPerspective_Id']
        )]
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

    public function getDocTypes(): array
    {
        return $this->docTypes;
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

    public function getDateTimeLocale(): string
    {
        return $this->dateTimeLocale;
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
     * @return UserDataObjectWorkspace[]
     */
    public function getDataObjectWorkspaces(): array
    {
        return $this->dataObjectWorkspaces;
    }

    /**
     * @return UserDocumentWorkspace[]
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
