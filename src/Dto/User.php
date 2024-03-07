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

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use Pimcore\Model\User as ModelUser;

readonly class User
{
    public function __construct(private ModelUser $user)
    {
    }

    /**
     * Alias for getName()
     *
     */
    public function getUsername(): ?string
    {
        return $this->user->getName();
    }

    public function getFirstname(): ?string
    {
        return $this->user->getFirstname();
    }

    public function getLastname(): ?string
    {
        return $this->user->getLastname();
    }

    public function getFullName(): string
    {
        return $this->user->getFullName();
    }

    public function getEmail(): ?string
    {
        return $this->user->getEmail();
    }

    public function getLanguage(): string
    {
        return $this->user->getLanguage();
    }

    public function isAdmin(): bool
    {
        return $this->user->isAdmin();
    }

    public function isActive(): bool
    {
        return $this->user->isActive();
    }

    /**
     * @return int[]
     */
    public function getRoles(): array
    {
        return $this->user->getRoles();
    }

    public function getWelcomescreen(): bool
    {
        return $this->user->getWelcomescreen();
    }

    public function getCloseWarning(): bool
    {
        return $this->user->getCloseWarning();
    }

    public function getMemorizeTabs(): bool
    {
        return $this->user->getMemorizeTabs();
    }

    public function getAllowDirtyClose(): bool
    {
        return $this->user->getAllowDirtyClose();
    }

    /**
     *
     * @return resource
     */
    public function getImage(?int $width = null, ?int $height = null)
    {
        return $this->user->getImage($width, $height);
    }

    /**
     * @return string[]
     */
    public function getContentLanguages(): array
    {
        return $this->user->getContentLanguages();
    }

    public function getActivePerspective(): string
    {
        return $this->user->getActivePerspective();
    }

    /**
     * Returns the first perspective name
     *
     * @internal
     */
    public function getFirstAllowedPerspective(): string
    {
        return $this->user->getFirstAllowedPerspective();
    }

    /**
     * Returns array of languages allowed for editing. If edit and view languages are empty all languages are allowed.
     * If only edit languages are empty (but view languages not) empty array is returned.
     *
     * @return string[]|null
     *
     * @internal
     *
     */
    public function getAllowedLanguagesForEditingWebsiteTranslations(): ?array
    {
        return $this->user->getAllowedLanguagesForEditingWebsiteTranslations();
    }

    /**
     * Returns array of languages allowed for viewing. If view languages are empty all languages are allowed.
     *
     * @return string[]|null
     *
     * @internal
     *
     */
    public function getAllowedLanguagesForViewingWebsiteTranslations(): ?array
    {
        return $this->user->getAllowedLanguagesForViewingWebsiteTranslations();
    }

    public function getLastLogin(): int
    {
        return $this->user->getLastLogin();
    }

    public function getKeyBindings(): ?string
    {
        return $this->user->getKeyBindings();
    }

    public function getProvider(): ?string
    {
        return $this->user->getProvider();
    }

    public function hasImage(): bool
    {
        return $this->user->hasImage();
    }
}
