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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserWelcomeScreen;

/**
 * Studio-only settings of a user; a missing row means "nothing set".
 *
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: UserSettings::TABLE_NAME)]
class UserSettings
{
    public const string TABLE_NAME = 'bundle_studio_user_settings';

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    private int $user;

    #[ORM\Column(type: 'string', nullable: true, length: UserWelcomeScreen::MAX_PATH_LENGTH)]
    private ?string $welcomeScreenPath = null;

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getWelcomeScreenPath(): ?string
    {
        return $this->welcomeScreenPath;
    }

    public function setWelcomeScreenPath(?string $welcomeScreenPath): void
    {
        $this->welcomeScreenPath = $welcomeScreenPath;
    }
}
