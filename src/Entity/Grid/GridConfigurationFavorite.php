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

    #[ORM\ManyToOne(targetEntity: GridConfiguration::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(name: 'configuration', referencedColumnName: 'id')]
    #[ORM\Id]
    private GridConfiguration $configuration;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $assetFolder = null;

    public function __construct(
    ) {
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getConfiguration(): GridConfiguration
    {
        return $this->configuration;
    }

    public function getAssetFolder(): ?int
    {
        return $this->assetFolder;
    }

    public function setAssetFolder(int $assetFolder): void
    {
        $this->assetFolder = $assetFolder;
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
