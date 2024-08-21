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
#[ORM\Table(name: GridConfigurationShare::TABLE_NAME)]
class GridConfigurationShare
{
    public const TABLE_NAME = 'bundle_studio_grid_configuration_shares';

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private int $user;

    #[ORM\ManyToOne(targetEntity: GridConfiguration::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(name: 'configuration', referencedColumnName: 'id')]
    #[ORM\Id]
    private GridConfiguration $configuration;

    public function __construct(
        int $user,
        GridConfiguration $configuration
    ) {
        $this->user = $user;
        $this->configuration = $configuration;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getConfiguration(): GridConfiguration
    {
        return $this->configuration;
    }
}
