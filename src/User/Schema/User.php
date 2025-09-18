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
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'User',
    description: 'Contains all information about a user',
    required: [
        'id', 'active', 'admin', 'classes', 'closeWarning', 'allowDirtyClose', 'contentLanguages', 'hasImage',
        'keyBindings', 'language', 'memorizeTabs', 'parentId', 'permissions', 'roles',
        'twoFactorAuthentication', 'websiteTranslationLanguagesEdit', 'websiteTranslationLanguagesView',
        'welcomeScreen', 'assetWorkspaces', 'dataObjectWorkspaces', 'documentWorkspaces', 'objectDependencies',
        'perspectives',
    ],
    type: 'object'
)]
final class User implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the User', type: 'integer', example: '1')]
        private readonly int $id,
        #[Property(description: 'Name of Folder or User', type: 'string', example: 'admin')]
        private readonly ?string $name,
        #[Property(description: 'Email of the User', type: 'string', example: '')]
        private readonly ?string $email,
        #[Property(description: 'Firstname of the User', type: 'string', example: '')]
        private readonly ?string $firstname,
        #[Property(description: 'Lastname of the User', type: 'string', example: '')]
        private readonly ?string $lastname,
        #[Property(description: 'If a User is active', type: 'boolean', example: true)]
        private readonly bool $active,
        #[Property(description: 'If User is admin', type: 'boolean', example: false)]
        private readonly bool $admin,
        #[Property(description: 'Classes the user is allows to see', type: 'object', example: ['CAR'])]
        private readonly array $classes,
        #[Property(description: 'Show close warning', type: 'boolean', example: true)]
        private readonly bool $closeWarning,
        #[Property(description: 'Allow Dirty Close', type: 'boolean', example: true)]
        private readonly bool $allowDirtyClose,
        #[Property(
            description: 'List of available content Language already sorted.',
            type: 'object',
            example: ['de', 'en']
        )]
        private readonly array $contentLanguages,
        #[Property(description: 'If User has an Image', type: 'boolean', example: true)]
        private readonly bool $hasImage,
        #[Property(description: 'Key Bindings', type: 'array', items: new Items(ref: KeyBinding::class))]
        private readonly array $keyBindings,
        #[Property(description: 'Language of the User', type: 'string', example: 'de')]
        private readonly string $language,
        #[Property(description: 'Timestamp of the last login', type: 'integer', example: '1718757677')]
        private readonly ?int $lastLogin,
        #[Property(description: 'Memorize Tabs', type: 'boolean', example: true)]
        private readonly bool $memorizeTabs,
        #[Property(description: 'Parent ID', type: 'integer', example: 2)]
        private readonly ?int $parentId,
        #[Property(description: 'List of permissions for the user', type: 'object', example: ['objects', 'documents'])]
        private readonly array $permissions,
        #[Property(description: 'ID List of roles the user is assigned', type: 'object', example: [12, 14])]
        private readonly array $roles,
        #[Property(
            ref: TwoFactorAuth::class,
            description: 'Two Factor Authentication',
            type: 'object'
        )]
        private readonly TwoFactorAuth $twoFactorAuthentication,
        #[Property(description: 'Website Translation Languages Edit', type: 'object', example: ['de', 'en'])]
        private readonly array $websiteTranslationLanguagesEdit,
        #[Property(description: 'Website Translation Languages View', type: 'object', example: ['de'])]
        private readonly array $websiteTranslationLanguagesView,
        #[Property(description: 'Show Welcome Screen', type: 'boolean', example: true)]
        private readonly bool $welcomeScreen,
        #[Property(description: 'Asset Workspace', type: 'array', items: new Items(ref: UserWorkspace::class))]
        private readonly array $assetWorkspaces,
        #[Property(
            description: 'Data Object Workspace',
            type: 'array',
            items: new Items(ref: UserDataObjectWorkspace::class)
        )]
        private readonly array $dataObjectWorkspaces,
        #[Property(
            description: 'Document Workspace',
            type: 'array',
            items: new Items(ref: UserDocumentWorkspace::class)
        )]
        private readonly array $documentWorkspaces,
        #[Property(ref: ObjectDependencies::class, description: 'Object Dependencies', type: 'object')]
        private readonly ObjectDependencies $objectDependencies,
        #[Property(
            description: 'Allowed studio perspectives',
            type: 'array',
            items: new Items(ref: PerspectiveConfig::class))
        ]
        private readonly array $perspectives = [],
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    public function isHasImage(): bool
    {
        return $this->hasImage;
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

    public function getParentId(): ?int
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

    public function getTwoFactorAuthentication(): TwoFactorAuth
    {
        return $this->twoFactorAuthentication;
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

    public function getObjectDependencies(): ObjectDependencies
    {
        return $this->objectDependencies;
    }

    /**
     * @return PerspectiveConfig[]
     */
    public function getPerspectives(): array
    {
        return $this->perspectives;
    }
}
