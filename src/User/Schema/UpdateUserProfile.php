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

/**
 * @internal
 */
#[Schema(
    title: 'User Profile',
    description: 'Schema to update a current User Profile.',
    required: [
        'firstname',
        'lastname',
        'email',
        'language',
        'dateTimeLocale',
        'welcomeScreen',
        'memorizeTabs',
        'contentLanguages',
        'keyBindings',
    ],
    type: 'object'
)]
final readonly class UpdateUserProfile
{
    public function __construct(
        #[Property(description: 'Firstname of the User', type: 'string', example: '')]
        private ?string $firstname,
        #[Property(description: 'Lastname of the User', type: 'string', example: '')]
        private ?string $lastname,
        #[Property(description: 'Email of the User', type: 'string', example: '')]
        private ?string $email,
        #[Property(description: 'Language of the User', type: 'string', example: 'de')]
        private string $language,
        #[Property(description: 'Date Time Locale for the User', type: 'string', example: '')]
        private string $dateTimeLocale,
        #[Property(type: 'boolean', example: true)]
        private bool $welcomeScreen,
        #[Property(type: 'boolean', example: true)]
        private bool $memorizeTabs,
        #[Property(
            description: 'List of available content Language already sorted.',
            type: 'object',
            example: ['de', 'en']
        )]
        private array $contentLanguages,
        #[Property(description: 'Key Bindings', type: 'array', items: new Items(ref: KeyBinding::class))]
        private array $keyBindings,
    ) {
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getDateTimeLocale(): string
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
}
