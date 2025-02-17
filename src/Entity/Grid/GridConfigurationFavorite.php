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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Grid;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: GridConfigurationFavorite::TABLE_NAME)]
class GridConfigurationFavorite
{
    public const TABLE_NAME = 'bundle_studio_grid_configuration_favorites';

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private int $user;

    #[ORM\ManyToOne(targetEntity: GridConfiguration::class, inversedBy: 'favorites')]
    #[ORM\JoinColumn(name: 'configuration', referencedColumnName: 'id')]
    #[ORM\Id]
    private GridConfiguration $configuration;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\Id]
    private int $folder;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $classId = null;

    public function getUser(): int
    {
        return $this->user;
    }

    public function getConfiguration(): GridConfiguration
    {
        return $this->configuration;
    }

    public function getFolder(): int
    {
        return $this->folder;
    }

    public function setFolder(int $folder): void
    {
        $this->folder = $folder;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function setClassId(?string $classId): void
    {
        $this->classId = $classId;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function setConfiguration(GridConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
