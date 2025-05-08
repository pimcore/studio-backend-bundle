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
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class UpdateProfileParameter
{
    public function __construct(
        private ?string $firstname,
        private ?string $lastname,
        private ?string $email,
        #[NotBlank(message: 'Language is required')]
        private string $language,
        private ?string $dateTimeLocale,
        private bool $welcomeScreen,
        private bool $memorizeTabs,
        private array $contentLanguages,
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
