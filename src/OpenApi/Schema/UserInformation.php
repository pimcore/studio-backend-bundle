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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorAuth;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'User Information',
    description: 'Information about the user',
    required: [
        'id', 'username', 'email', 'firstname', 'lastname', 'permissions', 'isAdmin', 'classes', 'docTypes',
        'language', 'dateTimeLocale', 'welcomeScreen', 'memorizeTabs', 'hasImage', 'contentLanguages',
        'keyBindings', 'activePerspective', 'perspectives', 'allowedLanguagesForEditingWebsiteTranslations',
        'allowedLanguagesForViewingWebsiteTranslations',
    ],
    type: 'object'
)]
final class UserInformation implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'User ID', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Username', type: 'string', example: 'admin')]
        private readonly string $username,
        #[Property(description: 'Email', type: 'string', example: '')]
        private readonly ?string $email,
        #[Property(description: 'Firstname', type: 'string', example: '')]
        private readonly ?string $firstname,
        #[Property(description: 'Lastname', type: 'string', example: '')]
        private readonly ?string $lastname,
        #[Property(
            description: 'Permissions',
            type: 'array',
            items: new Items(type: 'string', example: 'clear_cache')
        )]
        private readonly array $permissions,
        #[Property(description: 'If user is an admin user', type: 'boolean', example: false)]
        private readonly bool $isAdmin,
        #[Property(
            description: 'Allowed classes to create',
            type: 'array',
            items: new Items(type: 'string')
        )]
        private readonly array $classes,
        #[Property(
            description: 'Allowed doc types to create',
            type: 'array',
            items: new Items(type: 'string')
        )]
        private readonly array $docTypes,
        #[Property(description: 'User Language', type: 'string', example: 'en')]
        private readonly string $language,
        #[Property(description: 'Locale for dateTime', type: 'string', example: '')]
        private readonly ?string $dateTimeLocale,
        #[Property(description: 'Welcome Screen', type: 'boolean', example: true)]
        private readonly bool $welcomeScreen,
        #[Property(description: 'Memorize Tabs', type: 'boolean', example: true)]
        private readonly bool $memorizeTabs,
        #[Property(description: 'Has Image', type: 'boolean', example: true)]
        private readonly bool $hasImage,
        #[Property(
            description: 'List of available content Language already sorted.',
            type: 'object',
            example: ['de', 'en']
        )]
        private readonly array $contentLanguages,
        #[Property(
            description: 'List of valid website Languages to edit.',
            type: 'object',
            example: ['language' => 'de', 'display' => 'Deutsch']
        )]
        private readonly array $allowedLanguagesForEditingWebsiteTranslations,
        #[Property(
            description: 'List of valid website Languages to view.',
            type: 'object',
            example: ['language' => 'de', 'display' => 'Deutsch']
        )]
        private readonly array $allowedLanguagesForViewingWebsiteTranslations,
        #[Property(description: 'Key Bindings', type: 'array', items: new Items(ref: KeyBinding::class))]
        private readonly array $keyBindings,
        #[Property(
            description: 'Two Factor Authentication',
            type: 'array',
            items: new Items(ref: TwoFactorAuth::class)
        )]
        private readonly TwoFactorAuth $twoFactorAuthentication,
        #[Property(
            description: 'Active studio perspective ID',
            type: 'string',
            example: Perspectives::DEFAULT_ID->value)
        ]
        private readonly ?string $activePerspective = null,
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

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getUsername(): string
    {
        return $this->username;
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

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getDocTypes(): array
    {
        return $this->docTypes;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getDateTimeLocale(): ?string
    {
        return $this->dateTimeLocale;
    }

    public function isWelcomeScreen(): bool
    {
        return $this->welcomeScreen;
    }

    public function isMemorizeTabs(): bool
    {
        return $this->memorizeTabs;
    }

    public function isHasImage(): bool
    {
        return $this->hasImage;
    }

    /**
     * @return string[]
     */
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

    public function getTwoFactorAuthentication(): TwoFactorAuth
    {
        return $this->twoFactorAuthentication;
    }

    public function getActivePerspective(): ?string
    {
        return $this->activePerspective;
    }

    /**
     * @return PerspectiveConfig[]
     */
    public function getPerspectives(): array
    {
        return $this->perspectives;
    }

    public function getAllowedLanguagesForEditingWebsiteTranslations(): array
    {
        return $this->allowedLanguagesForEditingWebsiteTranslations;
    }

    public function getAllowedLanguagesForViewingWebsiteTranslations(): array
    {
        return $this->allowedLanguagesForViewingWebsiteTranslations;
    }
}
