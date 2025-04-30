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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Perspective;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: UserPerspectiveData::TABLE_NAME)]
class UserPerspectiveData
{
    public const string TABLE_NAME = 'bundle_studio_perspectives_user_perspectives';

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    private int $user;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $perspectives = null;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $activePerspective = null;

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getPerspectives(): ?string
    {
        return $this->perspectives;
    }

    public function setPerspectives(?string $perspectives): void
    {
        $this->perspectives = $perspectives;
    }

    public function getActivePerspective(): ?string
    {
        return $this->activePerspective;
    }

    public function setActivePerspective(?string $activePerspective): void
    {
        $this->activePerspective = $activePerspective;
    }
}
